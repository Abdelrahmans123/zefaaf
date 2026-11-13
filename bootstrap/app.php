<?php

use App\Exceptions\BookingConflict;
use App\Http\Middleware\Admin;
use App\Http\Middleware\ApiUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['api.user' => ApiUser::class, 'admin' => Admin::class]);
        $middleware->prependToPriorityList(SubstituteBindings::class, ApiUser::class);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->map(BookingConflict::class, fn ($error) => new HttpException(409, $error->getMessage(), $error));
    })
    ->create();
