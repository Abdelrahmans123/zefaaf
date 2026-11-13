<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/services', [BookingController::class, 'services']);
Route::post('/payments/webhook', WebhookController::class)->middleware('throttle:120,1');
Route::middleware(['api.user', 'throttle:60,1'])->group(function () {
    Route::post('/services/{service}/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/simulate-payment', [BookingController::class, 'simulate']);
});
