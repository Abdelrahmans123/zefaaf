<?php

namespace App\Http\Controllers;

use App\Actions\CancelBooking;
use App\Actions\ReserveBooking;
use App\Models\Booking;
use App\Models\Service;
use App\Services\LocalPaymentGateway;
use Illuminate\Http\Request;

class BookingController
{
    public function services()
    {
        return Service::withCount(['bookings as occupied' => fn ($q) => $q->whereIn('status', ['pending', 'confirmed'])])->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price_minor' => $s->price_minor, 'currency' => $s->currency, 'starts_at' => $s->starts_at, 'ends_at' => $s->ends_at, 'available' => (now()->gte($s->starts_at) && now()->lt($s->ends_at)) ? ($s->capacity === null ? null : max(0, $s->capacity - $s->occupied)) : 0]);
    }

    public function store(Request $r, Service $service, ReserveBooking $bookings)
    {
        $r->merge(['idempotency_key' => $r->header('Idempotency-Key')]);
        $data = $r->validate(['idempotency_key' => 'required|string|max:100']);
        $booking = $bookings->execute($r->user(), $service, $data['idempotency_key']);

        return response()->json($booking, $booking->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $r, Booking $booking)
    {
        $this->owner($r, $booking);

        return $booking;
    }

    public function cancel(Request $r, Booking $booking, CancelBooking $bookings)
    {
        $this->owner($r, $booking);

        return response()->json($bookings->execute($booking), 202);
    }

    public function simulate(Request $r, Booking $booking, LocalPaymentGateway $gateway)
    {
        abort_unless(config('booking.simulator_enabled') && app()->environment(['local', 'testing']), 404);
        $this->owner($r, $booking);
        $data = $r->validate(['outcome' => 'required|in:succeeded,failed,cancelled']);
        abort_unless($booking->status === 'pending' && ! $booking->cancel_requested && now()->lt($booking->expires_at), 409, 'Payment session has ended.');
        $gateway->ensure($booking);
        $status = $gateway->complete($booking->id, $data['outcome']);

        // Intentionally do not update the booking: demonstrates delayed/missing notifications.
        return ['provider_status' => $status, 'booking_id' => $booking->id];
    }

    private function owner(Request $r, Booking $booking): void
    {
        abort_unless($booking->user_id === $r->user()->id, 403);
    }
}
