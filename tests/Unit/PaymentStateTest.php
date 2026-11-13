<?php

namespace Tests\Unit;

use App\Services\PaymentState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentStateTest extends TestCase
{
    public static function states(): array
    {
        return [
            ['pending', 'succeeded', false, false, 'confirmed'],
            ['pending', 'succeeded', true, false, 'confirmed'],
            ['pending', 'succeeded', true, true, 'confirmed'],
            ['pending', 'pending', true, false, 'pending'],
            ['pending', 'cancelled', true, false, 'expired'],
            ['pending', 'cancelled', true, true, 'cancelled'],
            ['pending', 'failed', false, false, 'failed'],
            ['confirmed', 'failed', false, false, 'confirmed'],
            ['confirmed', 'cancelled', true, true, 'confirmed'],
            ['confirmed', 'succeeded', true, false, 'confirmed'],
            ['expired', 'pending', true, false, 'expired'],
        ];
    }

    #[DataProvider('states')]
    public function test_state_rules(string $current, string $provider, bool $expired, bool $cancel, string $expected): void
    {
        self::assertSame($expected, PaymentState::resolve($current, $provider, $expired, $cancel));
    }

    public function test_unknown_provider_response_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentState::resolve('pending', 'unknown', false, false);
    }
}
