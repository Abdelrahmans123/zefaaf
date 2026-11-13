<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    public function test_twelve_processes_compete_for_three_spots(): void
    {
        // Dedicated committed database: no wrapping RefreshDatabase transaction.
        $file = tempnam(sys_get_temp_dir(), 'zefaaf-race-');
        $barrier = $file.'-go';
        $workers = [];
        $connection = config('database.default');
        if ($connection === 'sqlite') {
            config(['database.connections.sqlite.database' => $file]);
            DB::purge('sqlite');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);
        try {
            $s = Service::create(['name' => 'Race', 'capacity' => 3, 'price_minor' => 1000, 'currency' => 'EGP', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
            for ($i = 0; $i < 12; $i++) {
                $u = User::create(['name' => "User $i", 'email' => "race$i@test.com", 'password' => 'password']);
                $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => $connection, 'DB_DATABASE' => $connection === 'sqlite' ? $file : config('database.connections.mysql.database')];
                if ($connection === 'mysql') {
                    foreach (['host', 'port', 'username', 'password', 'unix_socket'] as $key) {
                        $env[match ($key) {
                            'unix_socket' => 'DB_SOCKET',default => 'DB_'.strtoupper($key)
                        }] = (string) config('database.connections.mysql.'.$key);
                    }
                }
                $p = new Process([PHP_BINARY, base_path('tests/Support/concurrent-purchase.php'), (string) $u->id, (string) $s->id, $barrier], base_path(), $env);
                $p->setTimeout(40);
                $p->start();
                $workers[] = $p;
            }
            $deadline = microtime(true) + 20;
            while (count(glob($barrier.'.*')) < 12 && microtime(true) < $deadline) {
                usleep(10000);
            }
            self::assertCount(12, glob($barrier.'.*'), 'Every worker must be ready before releasing the barrier.');
            touch($barrier);
            $confirmed = 0;
            $rejected = 0;
            foreach ($workers as $p) {
                $p->wait();
                self::assertTrue($p->isSuccessful(), $p->getErrorOutput().$p->getOutput());
                $confirmed += trim($p->getOutput()) === 'confirmed' ? 1 : 0;
                $rejected += trim($p->getOutput()) === 'sold-out' ? 1 : 0;
            }
            self::assertSame(3, $confirmed);
            self::assertSame(9, $rejected);
            self::assertSame(3, Booking::where('status', 'confirmed')->count());
            self::assertSame(3, DB::table('provider_payments')->where('status', 'succeeded')->count());
        } finally {
            foreach ($workers as $p) {
                if ($p->isRunning()) {
                    $p->stop();
                }
            }
            Artisan::call('migrate:rollback', ['--force' => true]);
            DB::disconnect();
            foreach (glob($file.'*') as $path) {
                @unlink($path);
            }
        }
    }
}
