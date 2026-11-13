<?php

namespace Tests\Support;

use App\Contracts\ServiceLock;
use App\Models\Service;
use Closure;

/** Tests callback ordering only; real isolation is covered by ConcurrencyTest. */
final class InspectingServiceLock implements ServiceLock
{
    public bool $active = false;

    public function __construct(private Service $service) {}

    public function execute(int $serviceId, Closure $operation): mixed
    {
        $this->active = true;
        try {
            return $operation($this->service);
        } finally {
            $this->active = false;
        }
    }
}
