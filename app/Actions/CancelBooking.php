<?php

namespace App\Actions;

use App\Contracts\BookingStore;
use App\Contracts\ServiceLock;
use App\Exceptions\BookingConflict;
use App\Models\Booking;

final class CancelBooking
{
    public function __construct(private BookingStore $bookings, private ServiceLock $lock) {}

    public function execute(Booking $booking): Booking
    {
        return $this->lock->execute($booking->service_id, function () use ($booking) {
            $current = $this->bookings->get($booking->id);
            if ($current->status !== 'pending') {
                throw new BookingConflict('Only pending purchases can be cancelled.');
            }
            $this->bookings->update($current, ['cancel_requested' => true, 'next_reconcile_at' => now()]);

            return $current;
        });
    }
}
