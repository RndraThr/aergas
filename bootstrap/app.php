<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AergasRoleMiddleware;

// Set PHP execution time limits
ini_set('max_execution_time', '1500');
ini_set('max_input_time', '1500');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // 'role' => AergasRoleMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'customer.validated' => \App\Http\Middleware\CheckCustomerValidated::class,
            'user.active' => \App\Http\Middleware\EnsureUserIsActiveAndHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
