<?php

namespace Tests\Unit;

use App\Policies\ExponentialBackoff;
use PHPUnit\Framework\TestCase;

class RetryPolicyTest extends TestCase
{
    public function test_delays_increase_and_reach_the_documented_cap(): void
    {
        $policy = new ExponentialBackoff;
        self::assertSame([2, 4, 8, 16, 32, 64, 128, 256, 300, 300], array_map($policy->delaySeconds(...), range(1, 10)));
        self::assertSame(300, $policy->delaySeconds(PHP_INT_MAX));
    }

    public function test_failure_count_starts_at_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ExponentialBackoff)->delaySeconds(0);
    }
}
