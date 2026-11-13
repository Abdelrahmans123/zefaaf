<?php

namespace App\Services;

use App\Contracts\BookingStore;
use App\Contracts\PaymentInbox;
use App\Contracts\PaymentReconciliation;
use App\Contracts\ServiceLock;
use App\Models\Booking;

final class Reconciler implements PaymentReconciliation
{
    public function __construct(private PaymentGateway $gateway, private BookingStore $bookings, private ServiceLock $lock, private PaymentInbox $inbox) {}

    public function run(Booking $booking): void
    {
        $current = $this->bookings->get($booking->id);
        if ($current->status !== 'pending') {
            $this->inbox->acknowledge($current->id);

            return;
        }
        // External I/O must finish before entering the inventory transaction.
        $this->gateway->ensure($current);
        $status = ($current->cancel_requested || now()->gte($current->expires_at))
            ? $this->gateway->cancel($current->id) : $this->gateway->status($current->id);
        $this->lock->execute($current->service_id, function () use ($current, $status) {
            // Another worker may have finalized this booking during provider I/O.
            $latest = $this->bookings->get($current->id);
            $state = PaymentState::resolve($latest->status, $status, now()->gte($latest->expires_at), $latest->cancel_requested);
            if ($latest->status === 'pending') {
                $this->bookings->update($latest, [
                    'status' => $state, 'confirmed_at' => $state === 'confirmed' ? now() : null,
                    'next_reconcile_at' => now()->addSeconds(30), 'reconcile_failures' => 0, 'last_error' => null,
                ]);
            }
            $this->inbox->acknowledge($latest->id);
        });
    }
}
