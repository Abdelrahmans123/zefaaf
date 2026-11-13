<?php

namespace App\Infrastructure;

use App\Contracts\ServiceLock;
use App\Models\Service;
use Closure;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceLock implements ServiceLock
{
    public function execute(int $serviceId, Closure $operation): mixed
    {
        return DB::transaction(function () use ($serviceId, $operation) {
            // First statement is a write: serializes inventory on both SQLite and InnoDB.
            DB::table('services')->where('id', $serviceId)->increment('lock_version');

            return $operation(Service::findOrFail($serviceId));
        }, 5);
    }
}
