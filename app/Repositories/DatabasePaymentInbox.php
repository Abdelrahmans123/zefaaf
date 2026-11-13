<?php

namespace App\Repositories;

use App\Contracts\PaymentInbox;
use App\Exceptions\BookingConflict;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

final class DatabasePaymentInbox implements PaymentInbox
{
    public function record(string $eventId, string $bookingId, string $status): void
    {
        $hash = hash('sha256', json_encode([$bookingId, $status]));
        DB::table('payment_events')->insertOrIgnore(['id' => $eventId, 'booking_id' => $bookingId, 'payload_hash' => $hash, 'reported_status' => $status, 'received_at' => now()]);
        if (DB::table('payment_events')->where('id', $eventId)->value('payload_hash') !== $hash) {
            throw new BookingConflict('Event ID reused with a different payload.');
        }
    }

    public function acknowledge(string $bookingId): void
    {
        DB::table('payment_events')->where('booking_id', $bookingId)->whereNull('processed_at')->update(['processed_at' => now()]);
    }

    public function acknowledgeTerminalBookings(): void
    {
        DB::table('payment_events')->whereNull('processed_at')->whereIn('booking_id', Booking::select('id')->where('status', '!=', 'pending'))->update(['processed_at' => now()]);
    }
}
