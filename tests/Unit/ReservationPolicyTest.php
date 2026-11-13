<?php

namespace Tests\Unit;

use App\Exceptions\BookingConflict;
use App\Policies\ReservationPolicy;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReservationPolicyTest extends TestCase
{
    public static function availability(): array
    {
        return [
            'before opening' => [-1, 1, 0, false],
            'opening inclusive' => [0, 1, 0, true],
            'last spot' => [30, 2, 1, true],
            'sold out' => [30, 2, 2, false],
            'zero capacity' => [30, 0, 0, false],
            'unlimited' => [30, null, 1000, true],
            'closing exclusive' => [60, 1, 0, false],
            'after closing' => [61, null, 0, false],
        ];
    }

    #[DataProvider('availability')]
    public function test_purchase_window_and_capacity(int $minute, ?int $capacity, int $occupied, bool $allowed): void
    {
        $start = CarbonImmutable::parse('2026-11-11 12:00:00');
        if (! $allowed) {
            $this->expectException(BookingConflict::class);
        }
        (new ReservationPolicy)->assertAvailable($start->addMinutes($minute), $start, $start->addHour(), $capacity, $occupied);
        if ($allowed) {
            $this->addToAssertionCount(1);
        }
    }
}
