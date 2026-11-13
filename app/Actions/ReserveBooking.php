<?php

namespace App\Actions;

use App\Contracts\ReservationRepository;
use App\Contracts\ServiceLock;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Policies\ReservationPolicy;
use Carbon\CarbonImmutable;

final class ReserveBooking
{
    public function __construct(private ReservationRepository $bookings, private ServiceLock $lock, private ReservationPolicy $policy) {}

    public function execute(User $user, Service $service, string $key): Booking
    {
        return $this->lock->execute($service->id, function (Service $current) use ($user, $key) {
            $existing = $this->bookings->findByKey($user->id, $current->id, $key);
            if ($existing) {
                return $existing;
            }
            $active = $this->bookings->findActive($user->id, $current->id);
            if ($active) {
                $this->bookings->rememberKey($user->id, $current->id, $key, $active);

                return $active;
            }
            $now = CarbonImmutable::now();
            $this->policy->assertAvailable($now, $current->starts_at, $current->ends_at, $current->capacity, $this->bookings->occupied($current->id));
            $expiresAt = $now->addMinutes(config('booking.hold_minutes'))->min($current->ends_at);
            $booking = $this->bookings->create($user, $current, $key, $expiresAt);
            $this->bookings->rememberKey($user->id, $current->id, $key, $booking);

            return $booking;
        });
    }
}
