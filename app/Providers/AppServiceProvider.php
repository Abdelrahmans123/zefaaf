<?php

namespace App\Providers;

use App\Contracts\BookingStore;
use App\Contracts\PaymentInbox;
use App\Contracts\PaymentReconciliation;
use App\Contracts\ReservationRepository;
use App\Contracts\RetryPolicy;
use App\Contracts\ServiceLock;
use App\Infrastructure\DatabaseServiceLock;
use App\Policies\ExponentialBackoff;
use App\Repositories\DatabasePaymentInbox;
use App\Repositories\EloquentBookingRepository;
use App\Services\LocalPaymentGateway;
use App\Services\PaymentGateway;
use App\Services\Reconciler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, LocalPaymentGateway::class);
        $this->app->bind(PaymentReconciliation::class, Reconciler::class);
        $this->app->bind(ServiceLock::class, DatabaseServiceLock::class);
        $this->app->bind(ReservationRepository::class, EloquentBookingRepository::class);
        $this->app->bind(BookingStore::class, EloquentBookingRepository::class);
        $this->app->bind(PaymentInbox::class, DatabasePaymentInbox::class);
        $this->app->bind(RetryPolicy::class, ExponentialBackoff::class);
    }
}
