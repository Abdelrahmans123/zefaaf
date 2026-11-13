<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->boolean('is_admin')->default(false);
            $t->string('api_token', 64)->nullable()->unique();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('price_minor');
            $t->string('currency', 3)->default('EGP');
            $t->unsignedInteger('capacity')->nullable();
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->unsignedBigInteger('lock_version')->default(0);
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('service_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('idempotency_key', 100);
            $t->unsignedInteger('amount_minor');
            $t->string('currency', 3);
            $t->string('status')->default('pending');
            $t->timestamp('expires_at');
            $t->boolean('cancel_requested')->default(false);
            $t->timestamp('confirmed_at')->nullable();
            $t->text('admin_note')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->timestamp('next_reconcile_at')->nullable();
            $t->unsignedInteger('reconcile_failures')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'service_id', 'idempotency_key']);
            $t->index(['service_id', 'status']);
            $t->index(['status', 'next_reconcile_at']);
        });
        Schema::create('purchase_keys', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('service_id')->constrained();
            $t->string('key', 100);
            $t->foreignUuid('booking_id')->constrained();
            $t->unique(['user_id', 'service_id', 'key']);
        });
        // A local external-provider simulator: no FK to bookings, and separate commits.
        Schema::create('provider_payments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedInteger('amount_minor');
            $t->string('currency', 3);
            $t->string('status')->default('pending');
            $t->timestamp('expires_at');
            $t->timestamps();
        });
        Schema::create('payment_events', function (Blueprint $t) {
            $t->string('id', 100)->primary();
            $t->foreignUuid('booking_id')->constrained();
            $t->string('payload_hash', 64);
            $t->string('reported_status');
            $t->timestamp('received_at');
            $t->timestamp('processed_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['payment_events', 'provider_payments', 'purchase_keys', 'bookings', 'services', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
