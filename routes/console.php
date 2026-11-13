<?php

use App\Contracts\PaymentInbox;
use App\Models\Booking;
use App\Services\ReconciliationRunner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('bookings:reconcile {--all : Ignore retry backoff}', function (ReconciliationRunner $runner, PaymentInbox $inbox) {
    $count = 0;
    Booking::where('status', 'pending')->when(! $this->option('all'), fn ($q) => $q->where(fn ($q) => $q->whereNull('next_reconcile_at')->orWhere('next_reconcile_at', '<=', now())))
        ->chunkById(100, function ($bookings) use (&$count, $runner) {
            foreach ($bookings as $booking) {
                $runner->run($booking);
                $count++;
            }
        });
    // Recover inbox acknowledgements for already terminal bookings too.
    $inbox->acknowledgeTerminalBookings();
    $this->info("Reconciled {$count} pending bookings.");
});
Schedule::command('bookings:reconcile')->everyMinute()->withoutOverlapping();
