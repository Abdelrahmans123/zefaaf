<?php

namespace App\Services;

use App\Contracts\BookingStore;
use App\Contracts\PaymentReconciliation;
use App\Contracts\RetryPolicy;
use App\Contracts\ServiceLock;
use App\Models\Booking;
use Psr\Log\LoggerInterface;
use Throwable;

final class ReconciliationRunner
{
    public function __construct(private PaymentReconciliation $reconciler, private BookingStore $bookings, private ServiceLock $lock, private RetryPolicy $retry, private LoggerInterface $logger) {}

    public function run(Booking $booking): bool
    {
        try {
            $this->reconciler->run($booking);

            return true;
        } catch (Throwable $error) {
            $this->logger->error('Payment reconciliation failed.', ['booking_id' => $booking->id, 'exception' => $error]);
            $this->lock->execute($booking->service_id, function () use ($booking, $error) {
                $current = $this->bookings->get($booking->id);
                // A failing worker must not overwrite a successful worker's recovery metadata.
                if ($current->status !== 'pending') {
                    return;
                }
                $failures = $current->reconcile_failures + 1;
                $this->bookings->update($current, [
                    'reconcile_failures' => $failures, 'last_error' => substr($error->getMessage(), 0, 2000),
                    'next_reconcile_at' => now()->addSeconds($this->retry->delaySeconds($failures)),
                ]);
            });

            return false;
        }
    }
}
