<?php

return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => ['driver' => 'sqlite', 'database' => env('DB_DATABASE', database_path('database.sqlite')), 'prefix' => '', 'foreign_key_constraints' => true, 'busy_timeout' => 15000, 'journal_mode' => 'WAL', 'synchronous' => 'NORMAL'],
        'mysql' => ['driver' => 'mysql', 'host' => env('DB_HOST', '127.0.0.1'), 'port' => env('DB_PORT', '3306'), 'database' => env('DB_DATABASE', 'zefaaf'), 'username' => env('DB_USERNAME', 'zefaaf'), 'password' => env('DB_PASSWORD', 'secret'), 'unix_socket' => env('DB_SOCKET', ''), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => 'InnoDB'],
    ], 'migrations' => ['table' => 'migrations', 'update_date_on_publish' => true],
];
