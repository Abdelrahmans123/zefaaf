<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class LocalPaymentGateway implements PaymentGateway
{
    public function ensure(Booking $booking): void
    {
        DB::table('provider_payments')->insertOrIgnore(['id' => $booking->id, 'amount_minor' => $booking->amount_minor, 'currency' => $booking->currency, 'status' => 'pending', 'expires_at' => $booking->expires_at, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function status(string $id): string
    {
        return DB::table('provider_payments')->where('id', $id)->sole()->status;
    }

    public function cancel(string $id): string
    {
        DB::table('provider_payments')->where('id', $id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);

        return $this->status($id);
    }

    public function complete(string $id, string $outcome): string
    {
        abort_unless(in_array($outcome, ['succeeded', 'failed', 'cancelled'], true), 422);
        DB::table('provider_payments')->where('id', $id)->where('status', 'pending')->where('expires_at', '>', now())->update(['status' => $outcome, 'updated_at' => now()]);
        if ($this->status($id) === 'pending' && DB::table('provider_payments')->where('id', $id)->value('expires_at') <= now()->toDateTimeString()) {
            return $this->cancel($id);
        }

        return $this->status($id);
    }
}
