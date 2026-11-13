# Zefaaf — reliable bookings with Laravel

Laravel 13 / PHP 8.4 implementation of the supplied backend task. Includes a JSON booking API, a Blade admin interface, a local payment-provider simulator, scheduled recovery, and PHPUnit tests. No frontend build, payment account, Redis, or external queue service is required.

## Run locally

Requires PHP 8.4+, Composer 2, and PHP extensions `pdo_sqlite`, `mbstring`, `dom`, `xml`, `xmlwriter`, `ctype`, `fileinfo`, `openssl`, and `tokenizer`. The lock file includes the precise dependency versions.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

In another terminal, start recovery:

```bash
php artisan schedule:work
```

Visit **http://localhost:8000/admin/transactions**. Demo admin: `admin@example.test` / `password`. The admin can list/filter/view purchases, create reservations, edit notes/request cancellation, synchronize payments, and archive resolved transactions. Payment status is never freely editable; deletion retains the financial audit trail.

For a single recovery pass: `php artisan bookings:reconcile --all`. The scheduler otherwise runs every minute. Do not omit it: it creates payment sessions, resolves missing notifications, cancels abandoned sessions, and retries provider failures.

## Docker with MySQL

```bash
docker compose up -d --build db app
docker compose exec app php artisan migrate --seed
docker compose up -d scheduler
```

The same admin URL and demo credentials apply. Compose uses development credentials and a development application key. `docker compose down` preserves the MySQL volume. The CLI web server and demo credentials are for evaluation.

## Exercise the API

Alice's bearer token is `alice-demo-token`; Bob's is `bob-demo-token`. Tokens are stored hashed. All amounts are integer minor units; 150000 EGP minor units is EGP 1,500.00.

```bash
curl -s http://localhost:8000/api/services

curl -s -X POST http://localhost:8000/api/services/1/bookings \
  -H 'Authorization: Bearer alice-demo-token' \
  -H 'Accept: application/json' \
  -H 'Idempotency-Key: checkout-001'
```

Copy the returned booking UUID into `BOOKING_ID`:

```bash
BOOKING_ID=replace-with-returned-uuid
curl -s -X POST "http://localhost:8000/api/bookings/$BOOKING_ID/simulate-payment" \
  -H 'Authorization: Bearer alice-demo-token' \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"outcome":"succeeded"}'

php artisan bookings:reconcile --all

curl -s "http://localhost:8000/api/bookings/$BOOKING_ID" \
  -H 'Authorization: Bearer alice-demo-token' -H 'Accept: application/json'
```

The simulator deliberately commits payment independently without delivering a webhook. This demonstrates recovery after a lost notification. Supported outcomes: `succeeded`, `failed`, `cancelled`. It is available only when enabled **and** the environment is `local` or `testing`.

To send a signed notification, including a stale failure after success:

```bash
PAYLOAD=$(printf '{"event_id":"event-001","booking_id":"%s","status":"failed"}' "$BOOKING_ID")
SIGNATURE=$(printf '%s' "$PAYLOAD" | php -r 'echo hash_hmac("sha256", stream_get_contents(STDIN), "local-demo-webhook-secret-change-in-production");')
curl -s -X POST http://localhost:8000/api/payments/webhook \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -H "X-Payment-Signature: $SIGNATURE" --data-binary "$PAYLOAD"
```

The booking stays confirmed because reconciliation reads authoritative provider state. Sending the same event again has no additional effect. An event ID reused with different contents gets HTTP 409.

| Endpoint | Purpose |
| --- | --- |
| `GET /api/services` | Price, window, advisory availability (`null` means unlimited) |
| `POST /api/services/{service}/bookings` | Reserve; requires bearer token and `Idempotency-Key` |
| `GET /api/bookings/{booking}` | Read your purchase and recovery status |
| `POST /api/bookings/{booking}/cancel` | Request cancellation; HTTP 202 while provider resolution is pending |
| `POST /api/bookings/{booking}/simulate-payment` | Local provider outcome simulation |
| `POST /api/payments/webhook` | HMAC-signed provider notification |

No availability/window: HTTP 409. Missing/invalid token: 401. Another user's booking: 403. Invalid input: 422. Retrying a purchase key returns the same booking, including its terminal state. After failure or expiry, use a **new key** to start a new attempt. One active/confirmed purchase per user and service is an explicit business assumption.

## Tests

```bash
composer test
# A real multi-process race, not a loop of sequential requests:
php vendor/bin/phpunit tests/Feature/ConcurrencyTest.php
```

By default, business tests use SQLite in memory. The concurrency test uses a temporary SQLite file and starts 12 PHP processes behind a barrier, asserting exactly 3 confirmed payments and 9 sold-out responses. GitHub Actions runs the full suite on both SQLite and MySQL.

Run tests inside Docker without touching the demo database:

```bash
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app composer test
```

To test MySQL locally, create an **empty dedicated test database** and run:

```bash
DB_CONNECTION=mysql DB_DATABASE=zefaaf_test DB_HOST=127.0.0.1 \
DB_USERNAME=zefaaf DB_PASSWORD=secret composer test
```

Tests migrate/drop tables. Never point the test suite at a database containing data you need. The MySQL user must have access to the test database.

Coverage includes simultaneous last-spot purchases, duplicate and stale notifications, delayed/missing success notifications, repeated clicks, abandonment, cancellation/payment races, provider outages, crash recovery, old attempts after retries, ownership, webhook signatures, price snapshots, capacity limits, and admin CRUD protections.

**Verification in the implementation environment:** 31 unit tests and 5 HTTP smoke tests passed (75 assertions); routes load and Blade views compile. Database-backed tests were attempted but could not execute because `pdo_sqlite` is absent and sandbox restrictions block MySQL sockets. Docker and CI configuration are provided but were not executed here. Do not interpret the partial checks as a passing full suite.

## Design and submission

See [docs/architecture.md](docs/architecture.md) for the SOLID structure and design patterns. See [docs/approach.md](docs/approach.md) for correctness guarantees, trade-offs, provider requirements, and failure recovery.

This workspace has no configured Git repository or remote. The source is ready to commit and push to your own GitHub repository; no repository URL has been created.
