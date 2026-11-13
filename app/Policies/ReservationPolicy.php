<?php

namespace App\Policies;

use App\Exceptions\BookingConflict;
use Carbon\CarbonImmutable;

final class ReservationPolicy
{
    public function assertAvailable(CarbonImmutable $now, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?int $capacity, int $occupied): void
    {
        if ($now->lt($startsAt) || $now->gte($endsAt)) {
            throw new BookingConflict('Service is outside its purchase window.');
        }
        if ($capacity !== null && $occupied >= $capacity) {
            throw new BookingConflict('Service is no longer available.');
        }
    }
}
