<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password', 'api_token', 'remember_token'];

    protected function casts(): array
    {
        return ['is_admin' => 'boolean', 'password' => 'hashed'];
    }
}
