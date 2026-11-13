<?php

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;

interface ReservationRepository
{
    // All methods are used within ServiceLock, including reads before a write.
    public function findByKey(int $userId, int $serviceId, string $key): ?Booking;

    public function findActive(int $userId, int $serviceId): ?Booking;

    public function occupied(int $serviceId): int;

    public function create(User $user, Service $service, string $key, CarbonImmutable $expiresAt): Booking;

    public function rememberKey(int $userId, int $serviceId, string $key, Booking $booking): void;
}
