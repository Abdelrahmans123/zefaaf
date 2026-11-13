<?php

namespace App\Services;

use App\Models\Booking;

interface PaymentGateway
{
    // Must be idempotent by booking UUID, and terminal results must be immutable.
    public function ensure(Booking $booking): void;

    public function status(string $id): string;

    // Atomically cancel only if still pending; return succeeded if payment won the race.
    public function cancel(string $id): string;
}
