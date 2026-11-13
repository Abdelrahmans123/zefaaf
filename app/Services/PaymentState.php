<?php

namespace App\Services;

use InvalidArgumentException;

final class PaymentState
{
    public static function resolve(string $current, string $provider, bool $expired, bool $cancelRequested): string
    {
        if (! in_array($provider, ['pending', 'succeeded', 'failed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Unknown provider state.');
        }
        if ($current !== 'pending') {
            return $current;
        }

        return match ($provider) {
            'succeeded' => 'confirmed',
            'failed' => 'failed',
            'cancelled' => $expired && ! $cancelRequested ? 'expired' : 'cancelled',
            default => 'pending',
        };
    }
}
