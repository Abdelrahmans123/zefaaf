<?php

use App\Actions\ReserveBooking;
use App\Exceptions\BookingConflict;
use App\Models\Service;
use App\Models\User;
use App\Services\LocalPaymentGateway;
use App\Services\Reconciler;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
// The parent releases all processes together, after each has booted Laravel.
[$script,$userId,$serviceId,$barrier] = $argv;
file_put_contents($barrier.'.'.$userId, 'ready');
$deadline = microtime(true) + 20;
while (! file_exists($barrier)) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Barrier timed out.');
    }
    usleep(1000);
}
try {
    $booking = app(ReserveBooking::class)->execute(User::findOrFail($userId), Service::findOrFail($serviceId), 'race');
    $gateway = app(LocalPaymentGateway::class);
    $gateway->ensure($booking);
    $gateway->complete($booking->id, 'succeeded');
    app(Reconciler::class)->run($booking);
    echo 'confirmed';
} catch (BookingConflict $e) {
    echo 'sold-out';
}
