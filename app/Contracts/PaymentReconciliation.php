<?php

namespace App\Contracts;

use App\Models\Booking;

interface PaymentReconciliation
{
    /** Reconcile once; throw on failure so the caller can persist a retry. */
    public function run(Booking $booking): void;
}
