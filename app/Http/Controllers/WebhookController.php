<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentInbox;
use App\Models\Booking;
use App\Services\ReconciliationRunner;
use Illuminate\Http\Request;

class WebhookController
{
    public function __invoke(Request $r, ReconciliationRunner $reconciler, PaymentInbox $inbox)
    {
        $secret = config('booking.webhook_secret');
        abort_unless($secret && hash_equals(hash_hmac('sha256', $r->getContent(), $secret), (string) $r->header('X-Payment-Signature')), 401);
        $data = $r->validate(['event_id' => 'required|string|max:100', 'booking_id' => 'required|uuid|exists:bookings,id', 'status' => 'required|in:succeeded,failed,cancelled,pending']);
        $inbox->record($data['event_id'], $data['booking_id'], $data['status']);
        // Acknowledge only after durable inbox insert. The periodic reconciler recovers crashes.
        $reconciler->run(Booking::findOrFail($data['booking_id']));

        return response()->json(['accepted' => true], 202);
    }
}
