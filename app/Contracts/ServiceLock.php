<?php

namespace App\Contracts;

use App\Models\Service;
use Closure;

interface ServiceLock
{
    /** Execute atomically after locking the service; pass its current snapshot to the callback.
     * Roll back all repository/inbox writes on failure. Implementations must use the same DB connection.
     *
     * @template T
     *
     * @param  Closure(Service): T  $operation
     * @return T
     */
    public function execute(int $serviceId, Closure $operation): mixed;
}
