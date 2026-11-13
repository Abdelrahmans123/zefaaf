<?php

namespace App\Contracts;

interface PaymentInbox
{
    /** Durably accept duplicate identical events, reject conflicting reuse of an ID. */
    public function record(string $eventId, string $bookingId, string $status): void;

    public function acknowledge(string $bookingId): void;

    public function acknowledgeTerminalBookings(): void;
}
