<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiUser
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        abort_unless($token, 401, 'Bearer token required.');
        $user = User::where('api_token', hash('sha256', $token))->first();
        abort_unless($user, 401, 'Invalid token.');
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
