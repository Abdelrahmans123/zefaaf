<?php

namespace App\Contracts;

interface RetryPolicy
{
    /** Positive delay in seconds after the given consecutive failure (starting at 1). */
    public function delaySeconds(int $failureCount): int;
}
