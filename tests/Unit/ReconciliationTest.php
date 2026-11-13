<?php

namespace Tests\Unit;

use App\Contracts\BookingStore;
use App\Contracts\PaymentInbox;
use App\Contracts\RetryPolicy;
use App\Models\Booking;
use App\Models\Service;
use App\Services\PaymentGateway;
use App\Services\Reconciler;
use App\Services\ReconciliationRunner;
use Psr\Log\NullLogger;
use Tests\Support\InspectingServiceLock;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    private function booking(string $status = 'pending'): Booking
    {
        return new Booking(['id' => 'one', 'service_id' => 1, 'status' => $status, 'expires_at' => now()->addMinutes(5), 'cancel_requested' => false, 'reconcile_failures' => 0]);
    }

    public function test_provider_io_is_outside_lock_and_state_and_acknowledgement_are_inside(): void
    {
        $b = $this->booking();
        $lock = new InspectingServiceLock(new Service(['id' => 1]));
        $store = $this->createMock(BookingStore::class);
        $store->method('get')->willReturn($b);
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->expects(self::once())->method('ensure')->willReturnCallback(function () use ($lock) {
            self::assertFalse($lock->active);
        });
        $gateway->method('status')->willReturnCallback(function () use ($lock) {
            self::assertFalse($lock->active);

            return 'succeeded';
        });
        $store->expects(self::once())->method('update')->willReturnCallback(function ($booking, $changes) use ($lock) {
            self::assertTrue($lock->active);
            self::assertSame('confirmed', $changes['status']);
        });
        $inbox = $this->createMock(PaymentInbox::class);
        $inbox->expects(self::once())->method('acknowledge')->willReturnCallback(function () use ($lock) {
            self::assertTrue($lock->active);
        });
        (new Reconciler($gateway, $store, $lock, $inbox))->run($b);
    }

    public function test_concurrent_worker_cannot_overwrite_a_terminal_result(): void
    {
        $b = $this->booking();
        $lock = new InspectingServiceLock(new Service(['id' => 1]));
        $store = $this->createMock(BookingStore::class);
        $store->method('get')->willReturnOnConsecutiveCalls($b, $this->booking('confirmed'));
        $store->expects(self::never())->method('update');
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->expects(self::once())->method('status')->willReturn('pending');
        $inbox = $this->createMock(PaymentInbox::class);
        $inbox->expects(self::once())->method('acknowledge');
        (new Reconciler($gateway, $store, $lock, $inbox))->run($b);
    }

    public function test_outage_uses_injected_retry_strategy_without_releasing_capacity(): void
    {
        $b = $this->booking();
        $lock = new InspectingServiceLock(new Service(['id' => 1]));
        $store = $this->createMock(BookingStore::class);
        $store->method('get')->willReturn($b);
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->expects(self::once())->method('ensure')->willThrowException(new \RuntimeException('Offline'));
        $retry = $this->createMock(RetryPolicy::class);
        $retry->expects(self::once())->method('delaySeconds')->with(1)->willReturn(42);
        $this->freezeTime();
        $store->expects(self::once())->method('update')->willReturnCallback(function ($booking, $changes) use ($lock) {
            self::assertTrue($lock->active);
            self::assertArrayNotHasKey('status', $changes);
            self::assertSame(1, $changes['reconcile_failures']);
            self::assertTrue($changes['next_reconcile_at']->equalTo(now()->addSeconds(42)));
        });
        $reconciler = new Reconciler($gateway, $store, $lock, $this->createStub(PaymentInbox::class));
        self::assertFalse((new ReconciliationRunner($reconciler, $store, $lock, $retry, new NullLogger))->run($b));
    }

    public function test_failing_worker_does_not_write_retry_metadata_after_another_worker_confirms(): void
    {
        $b = $this->booking();
        $lock = new InspectingServiceLock(new Service(['id' => 1]));
        $store = $this->createMock(BookingStore::class);
        $store->method('get')->willReturnOnConsecutiveCalls($b, $this->booking('confirmed'));
        $store->expects(self::never())->method('update');
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->expects(self::once())->method('ensure')->willThrowException(new \RuntimeException('Timeout'));
        $retry = $this->createMock(RetryPolicy::class);
        $retry->expects(self::never())->method('delaySeconds');
        $reconciler = new Reconciler($gateway, $store, $lock, $this->createStub(PaymentInbox::class));
        self::assertFalse((new ReconciliationRunner($reconciler, $store, $lock, $retry, new NullLogger))->run($b));
    }
}
