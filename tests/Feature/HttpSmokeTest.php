<?php

namespace Tests\Feature;

use App\Exceptions\BookingConflict;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HttpSmokeTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Administrator sign in');
    }

    public function test_guests_are_redirected_from_admin(): void
    {
        $this->get('/admin/transactions')->assertRedirect('/login');
    }

    public function test_unsigned_webhook_is_rejected(): void
    {
        $this->postJson('/api/payments/webhook')->assertUnauthorized();
    }

    public function test_api_requires_token(): void
    {
        $this->postJson('/api/services/1/bookings')->assertUnauthorized();
    }

    public function test_domain_conflicts_are_translated_to_http_409(): void
    {
        Route::get('/_test/conflict', fn () => throw new BookingConflict('Sold out.'));
        $this->getJson('/_test/conflict')->assertStatus(409)->assertJsonPath('message', 'Sold out.');
    }
}
