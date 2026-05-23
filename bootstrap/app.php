<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function() {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/portal_api.php'));
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/admin_api.php'));
        }
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
    // $middleware->validateCsrfTokens(except: [
    //     '/success',
    //     '/cancel',
    //     '/fail',
    //     '/ipn',
    //     '/pay-via-ajax',
    // ]);
    $middleware->validateCsrfTokens(except: [
        'payments/cancel',
        'payments/success',
        'payments/fail'
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
