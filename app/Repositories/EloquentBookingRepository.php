<?php

namespace App\Repositories;

use App\Contracts\BookingStore;
use App\Contracts\ReservationRepository;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentBookingRepository implements BookingStore, ReservationRepository
{
    public function get(string $id): Booking
    {
        return Booking::findOrFail($id);
    }

    public function update(Booking $booking, array $changes): void
    {
        $booking->update($changes);
    }

    public function findByKey(int $userId, int $serviceId, string $key): ?Booking
    {
        $id = DB::table('purchase_keys')->where('user_id', $userId)->where('service_id', $serviceId)->where('key', $key)->value('booking_id');

        return $id ? $this->get($id) : null;
    }

    public function findActive(int $userId, int $serviceId): ?Booking
    {
        return Booking::where('user_id', $userId)->where('service_id', $serviceId)->whereIn('status', ['pending', 'confirmed'])->first();
    }

    public function occupied(int $serviceId): int
    {
        return Booking::where('service_id', $serviceId)->whereIn('status', ['pending', 'confirmed'])->count();
    }

    public function create(User $user, Service $service, string $key, CarbonImmutable $expiresAt): Booking
    {
        return Booking::create([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'service_id' => $service->id,
            'idempotency_key' => $key, 'amount_minor' => $service->price_minor,
            'currency' => $service->currency, 'expires_at' => $expiresAt,
            'next_reconcile_at' => now(),
        ])->refresh();
    }

    public function rememberKey(int $userId, int $serviceId, string $key, Booking $booking): void
    {
        DB::table('purchase_keys')->insert(['user_id' => $userId, 'service_id' => $serviceId, 'key' => $key, 'booking_id' => $booking->id]);
    }
}
