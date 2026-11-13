<?php

namespace Tests\Feature;

use App\Actions\CancelBooking;
use App\Actions\ReserveBooking;
use App\Exceptions\BookingConflict;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\LocalPaymentGateway;
use App\Services\PaymentGateway;
use App\Services\Reconciler;
use App\Services\ReconciliationRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::create(['name' => 'One spot', 'price_minor' => 10000, 'currency' => 'EGP', 'capacity' => 1, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $this->alice = User::create(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'password', 'api_token' => hash('sha256', 'alice')]);
        $this->bob = User::create(['name' => 'Bob', 'email' => 'bob@test.com', 'password' => 'password', 'api_token' => hash('sha256', 'bob')]);
    }

    private function reserve(?User $user = null, string $key = 'click-one'): Booking
    {
        return app(ReserveBooking::class)->execute($user ?? $this->alice, $this->service, $key);
    }

    private function outcome(Booking $booking, string $status): void
    {
        $g = app(LocalPaymentGateway::class);
        $g->ensure($booking);
        $g->complete($booking->id, $status);
    }

    private function webhook(Booking $booking, string $id, string $status)
    {
        $json = json_encode(['booking_id' => $booking->id, 'event_id' => $id, 'status' => $status]);

        return $this->call('POST', '/api/payments/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_PAYMENT_SIGNATURE' => hash_hmac('sha256', $json, 'test-secret')], $json);
    }

    public function test_repeated_clicks_and_new_keys_return_same_purchase(): void
    {
        $a = $this->reserve();
        self::assertSame($a->id, $this->reserve()->id);
        self::assertSame($a->id, $this->reserve(key: 'another-click')->id);
        self::assertSame(1, Booking::count());
        self::assertSame(2, DB::table('purchase_keys')->count());
        $this->withToken('bob')->postJson("/api/services/{$this->service->id}/bookings", [], ['Idempotency-Key' => 'bob-click'])->assertStatus(409);
    }

    public function test_duplicate_and_out_of_order_webhooks_do_not_reverse_success(): void
    {
        $b = $this->reserve();
        $this->outcome($b, 'succeeded');
        $this->webhook($b, 'event-1', 'succeeded')->assertAccepted();
        $this->webhook($b, 'event-1', 'succeeded')->assertAccepted();
        $this->webhook($b, 'old-failure', 'failed')->assertAccepted();
        $this->webhook($b, 'old-cancel', 'cancelled')->assertAccepted();
        self::assertSame('confirmed', $b->fresh()->status);
        self::assertSame(3, DB::table('payment_events')->count());
        self::assertSame(1, Booking::where('status', 'confirmed')->count());
    }

    public function test_delayed_success_after_deadline_retains_its_reserved_spot(): void
    {
        $b = $this->reserve();
        $this->outcome($b, 'succeeded');
        $this->travel(20)->minutes();
        $this->artisan('bookings:reconcile --all')->assertSuccessful();
        self::assertSame('confirmed', $b->fresh()->status);
        $this->withToken('bob')->postJson("/api/services/{$this->service->id}/bookings", [], ['Idempotency-Key' => 'bob'])->assertStatus(409);
    }

    public function test_abandoned_purchase_expires_and_cannot_pay_later(): void
    {
        $b = $this->reserve();
        $this->travel(20)->minutes();
        $this->artisan('bookings:reconcile --all')->assertSuccessful();
        self::assertSame('expired', $b->fresh()->status);
        self::assertSame('cancelled', app(LocalPaymentGateway::class)->complete($b->id, 'succeeded'));
        $new = $this->reserve($this->bob);
        self::assertNotSame($b->id, $new->id);
        $this->webhook($b, 'stale-success', 'succeeded')->assertAccepted();
        self::assertSame('expired', $b->fresh()->status);
    }

    public function test_provider_error_preserves_capacity_and_recovers(): void
    {
        $b = $this->reserve();
        $this->travel(20)->minutes();
        $broken = \Mockery::mock(PaymentGateway::class);
        $broken->shouldReceive('ensure')->once()->andThrow(new \RuntimeException('Temporary provider outage'));
        $this->app->instance(PaymentGateway::class, $broken);
        self::assertFalse(app(ReconciliationRunner::class)->run($b));
        $this->app->forgetInstance(PaymentGateway::class);
        self::assertSame('pending', $b->fresh()->status);
        self::assertSame(1, $b->fresh()->reconcile_failures);
        $this->withToken('bob')->postJson("/api/services/{$this->service->id}/bookings", [], ['Idempotency-Key' => 'bob'])->assertStatus(409);
        app(Reconciler::class)->run($b);
        self::assertSame('expired', $b->fresh()->status);
        self::assertSame(0, $b->fresh()->reconcile_failures);
    }

    public function test_crash_after_provider_creation_is_safe_to_retry(): void
    {
        $b = $this->reserve();
        app(LocalPaymentGateway::class)->ensure($b);
        app(Reconciler::class)->run($b);
        app(Reconciler::class)->run($b);
        self::assertSame(1, DB::table('provider_payments')->count());
        $this->outcome($b, 'succeeded');
        $this->artisan('bookings:reconcile --all')->assertSuccessful();
        self::assertSame('confirmed', $b->fresh()->status);
    }

    public function test_old_attempt_does_not_change_new_attempt(): void
    {
        $old = $this->reserve();
        $this->outcome($old, 'failed');
        $this->webhook($old, 'failed-1', 'failed')->assertAccepted();
        $this->webhook($old, 'failed-1', 'failed')->assertAccepted();
        $new = $this->reserve(key: 'retry');
        $this->outcome($new, 'succeeded');
        $this->webhook($new, 'new-success', 'succeeded')->assertAccepted();
        $this->webhook($old, 'old-cancel', 'cancelled')->assertAccepted();
        self::assertSame('confirmed', $new->fresh()->status);
        self::assertSame('failed', $old->fresh()->status);
        self::assertSame($old->id, $this->reserve()->id);
    }

    public function test_cancel_and_success_race_preserves_the_success(): void
    {
        $b = $this->reserve();
        app(CancelBooking::class)->execute($b);
        $this->outcome($b, 'succeeded');
        app(Reconciler::class)->run($b);
        self::assertSame('confirmed', $b->fresh()->status);
    }

    public function test_cancellation_is_idempotent_and_releases_capacity_only_after_provider_ack(): void
    {
        $b = $this->reserve();
        app(CancelBooking::class)->execute($b);
        app(CancelBooking::class)->execute($b);
        self::assertSame('pending', $b->fresh()->status);
        app(Reconciler::class)->run($b);
        $this->webhook($b, 'cancel-1', 'cancelled')->assertAccepted();
        $this->webhook($b, 'cancel-1', 'cancelled')->assertAccepted();
        self::assertSame('cancelled', $b->fresh()->status);
        self::assertNotSame($b->id, $this->reserve($this->bob)->id);
    }

    public function test_event_id_cannot_be_reused_with_different_contents(): void
    {
        $b = $this->reserve();
        $this->webhook($b, 'one', 'pending')->assertAccepted();
        $this->webhook($b, 'one', 'succeeded')->assertStatus(409);
    }

    public function test_webhook_signature_and_purchase_ownership_are_enforced(): void
    {
        $b = $this->reserve();
        $this->postJson('/api/payments/webhook', ['booking_id' => $b->id])->assertUnauthorized();
        $this->withToken('bob')->getJson('/api/bookings/'.$b->id)->assertForbidden();
        $this->withToken('alice')->getJson('/api/bookings/'.$b->id)->assertOk();
    }

    public function test_price_is_snapshotted_and_purchase_window_is_enforced(): void
    {
        $b = $this->reserve();
        $this->service->update(['price_minor' => 20000]);
        self::assertSame(10000, $b->amount_minor);
        $this->service->update(['ends_at' => now()]);
        $this->withToken('bob')->postJson("/api/services/{$this->service->id}/bookings", [], ['Idempotency-Key' => 'bob'])->assertStatus(409);
    }

    public function test_admin_crud_cannot_forge_success_or_delete_audit_history(): void
    {
        $this->actingAs($this->alice)->get('/admin/transactions')->assertForbidden();
        $this->alice->update(['is_admin' => true]);
        $this->actingAs($this->alice)->get('/admin/transactions/create')->assertOk();
        $this->post('/admin/transactions', ['user_id' => $this->bob->id, 'service_id' => $this->service->id, 'idempotency_key' => (string) Str::uuid()])->assertRedirect();
        $b = Booking::sole();
        $this->get('/admin/transactions')->assertOk()->assertSee('One spot');
        $this->get('/admin/transactions/'.$b->id)->assertOk();
        $this->patch('/admin/transactions/'.$b->id, ['admin_note' => 'Reviewed', 'status' => 'confirmed'])->assertRedirect();
        self::assertSame('pending', $b->fresh()->status);
        $this->delete('/admin/transactions/'.$b->id)->assertStatus(409);
        $this->outcome($b, 'failed');
        app(Reconciler::class)->run($b);
        $this->delete('/admin/transactions/'.$b->id)->assertRedirect();
        self::assertNotNull($b->fresh()->archived_at);
        self::assertSame(1, Booking::count());
    }

    public function test_unlimited_capacity_and_zero_capacity(): void
    {
        $this->service->update(['capacity' => null]);
        $this->reserve();
        $this->reserve($this->bob);
        self::assertSame(2, Booking::count());
        $this->service->update(['capacity' => 0]);
        $third = User::create(['name' => 'Third', 'email' => 'third@test.com', 'password' => 'password']);
        $this->expectException(BookingConflict::class);
        $this->reserve($third);
    }
}
