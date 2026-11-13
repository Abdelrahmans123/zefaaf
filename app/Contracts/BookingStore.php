<?php

namespace App\Contracts;

use App\Models\Booking;

interface BookingStore
{
    public function get(string $id): Booking;

    /** Persist only the supplied changes; do not write a stale model snapshot. */
    public function update(Booking $booking, array $changes): void;
}
