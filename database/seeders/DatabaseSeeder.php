<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeding is restricted to local/testing environments.');
        }
        User::firstOrCreate(['email' => 'admin@example.test'], ['name' => 'Demo Admin', 'password' => 'password', 'is_admin' => true]);
        foreach (['alice', 'bob'] as $name) {
            User::firstOrCreate(['email' => "{$name}@example.test"], ['name' => ucfirst($name), 'password' => 'password', 'api_token' => hash('sha256', "{$name}-demo-token")]);
        }
        Service::firstOrCreate(['name' => 'Wedding planning consultation'], ['price_minor' => 150000, 'currency' => 'EGP', 'capacity' => 10, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        Service::firstOrCreate(['name' => 'Last available photography session'], ['price_minor' => 250000, 'currency' => 'EGP', 'capacity' => 1, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }
}
