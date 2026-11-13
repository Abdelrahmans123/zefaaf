<?php

namespace App\Policies;

use App\Contracts\RetryPolicy;
use InvalidArgumentException;

final class ExponentialBackoff implements RetryPolicy
{
    public function delaySeconds(int $failureCount): int
    {
        if ($failureCount < 1) {
            throw new InvalidArgumentException('Failure count must be positive.');
        }

        return min(300, 2 ** min($failureCount, 9));
    }
}
