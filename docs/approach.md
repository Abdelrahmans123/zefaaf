# Approach

## Availability and incomplete purchases

Each booking temporarily reserves one spot for 15 minutes, capped by the service's purchase deadline. Pending reservations and confirmed purchases both count toward occupancy. All inventory decisions first write the service row inside a transaction; competing requests for that service must wait before checking capacity. We then check the window, count occupancy, and create the reservation plus its idempotency mapping in that same transaction. The first statement is a write so SQLite also serializes the critical section. MySQL uses an InnoDB row lock, allowing unrelated services to progress independently. Transactions retry deadlocks up to five times.

Expiry alone never frees inventory. The reconciler first asks the provider to atomically cancel an unpaid payment session. If payment already succeeded, cancellation returns success and the booking confirms against its existing reserved spot. If cancellation wins, the provider guarantees that the session cannot subsequently charge. Only then do we release capacity. This closes the dangerous gap between releasing a reservation and learning about an earlier successful payment.

The trade-off is conservative availability: provider outages keep spots reserved beyond 15 minutes. Normal expiry resolves on the next scheduler run; outages resolve through indefinite retries with capped backoff. A time limit with immediate release would improve apparent availability but could charge a customer for a spot already sold. We prioritize avoiding that outcome. An expired user must start a new booking, with a new idempotency key and a fresh availability check.

Displayed availability is a snapshot, not a promise. The reservation endpoint is authoritative. Service purchase windows are half-open: `starts_at <= now < ends_at`. Late notification of an earlier payment can confirm after the window closes; new payments cannot start then. Zero capacity means sold out, `null` means unlimited.

## Provider contract

The interface requires idempotent session creation by booking UUID, an authoritative status read, terminal states that never reverse, and atomic “cancel if still unpaid.” A session has an amount/currency snapshot and a payment deadline. The included local simulator enforces these using conditional database updates. Its writes occur outside the inventory transaction, so a provider success can survive a later application failure. It does not make network calls and stores no card data.

A production adapter must bind each UUID to exactly one merchant payment session, validate the amount and currency, enforce the payment deadline, and meet these cancellation guarantees. Authorization followed by controlled capture is an alternative for providers without such guarantees. If a provider cannot guarantee that cancelled/failed sessions cannot later succeed, this design needs a late-payment refund workflow before using that provider. Exactly-once delivery is not assumed.

## Duplicate actions and events

Purchase idempotency is scoped to `(user, service, key)` with a database unique constraint. We retain mappings permanently, including alternate keys submitted while the same user already has a pending or confirmed booking. Repeating an old key returns its original result even after expiry, failure, or archive. A new attempt requires a new key. Within this task, each user can have at most one pending/confirmed booking per service. Failed/expired attempts do not prevent a new one.

Signed webhooks are durably inserted into an inbox with a globally unique provider event ID. Repeated identical events are safe. Reuse of an ID with a different booking/status is rejected. The endpoint authenticates HMAC over the exact request body before processing it. Event payloads are hints; they do not directly set purchase state. Reconciliation reads authoritative provider state, so an old failure notification cannot undo success and old attempt events affect only their own UUID. The simulator does not permit a terminal payment to reverse.

## Failures and recovery

The pending booking is also a durable work item: it is committed before any provider action, and records its next reconciliation time, retry count, and last error. This is a small database-backed work queue; no fragile “commit booking, then enqueue” gap exists. Scheduled workers scan pending bookings in batches. Missing webhooks are recovered through polling. Terminal transitions and event acknowledgement share an inventory transaction.

| Failure point | Recovery |
| --- | --- |
| Before booking commit | Transaction rolls back; client retries its key |
| After booking commit, before creating payment | Scheduler finds pending work and creates the session |
| Provider creates session, application crashes | Same UUID returns the same provider session |
| Provider succeeds, webhook is lost | Scheduler reads success and confirms the held spot |
| Inbox insert succeeds, process crashes | Pending-booking scan reconciles; terminal-event sweep acknowledges remaining inbox rows |
| Provider temporarily unavailable | Keep capacity reserved; persist error; retry with capped exponential backoff |
| Cancellation races with payment | Provider's atomic transition chooses one terminal result |
| Two workers reconcile the same booking | Provider operations are idempotent; service lock and terminal-state guard prevent double transition |
| User closes the browser | Deadline plus scheduled cancellation resolves the pending booking |

The retry timer is capped at 300 seconds. Workers can safely repeat operations. A pending read just before a cancellation request may defer cancellation until the next pass; capacity remains protected. Logs plus admin recovery fields make persistent failures visible. Local scheduler overlap prevention is an optimization; correctness does not depend on it.

## Admin and scope

The admin interface uses session authentication, an administrator role check, CSRF protection, and login throttling. APIs use hashed bearer tokens and ownership checks. Admin creation uses the same inventory service. Updates allow notes and cancellation requests, never arbitrary status changes. Delete means archive a resolved transaction while retaining provider/event history and idempotency keys. Archived confirmed purchases continue to consume capacity.

This implementation excludes refunds of already confirmed purchases, capacity/price administration, multiple quantities, customer registration/token issuance, email delivery, live payment adapters, chargebacks, and observability infrastructure. Demo seeding supplies users, services, and tokens; it is restricted to local/testing environments. The simulator and bundled credentials are evaluation tools.

The service row is intentionally a contention point for a popular service. There is an indexed occupancy query and no provider I/O while the inventory lock is held. For much larger inventories, consider preallocated spot rows or conditional inventory counters, benchmark contention, partition the reconciliation workload, and add provider timeouts, rate limiting, jitter, and operational alerts. SQLite is suitable for local evaluation; the MySQL path is intended for concurrent deployments. Database permissions must prevent untrusted direct writes that bypass the booking service.

Implementation references: Laravel's [database transactions](https://laravel.com/docs/13.x/database#database-transactions), [query builder locking](https://laravel.com/docs/13.x/queries#pessimistic-locking), and [scheduler](https://laravel.com/docs/13.x/scheduling).
