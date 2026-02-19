<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'prevent.back' => \App\Http\Middleware\PreventBackHistory::class,
            'optimize.login' => \App\Http\Middleware\OptimizeLoginSession::class,
            'optimize.delivery.auth' => \App\Http\Middleware\OptimizeDeliveryPartnerAuth::class,
            'delivery.partner.status' => \App\Http\Middleware\CheckDeliveryPartnerStatus::class,
        ]);
        
        // Removed global OptimizeLoginSession middleware - apply it only where needed
        // $middleware->web(append: [
        //     \App\Http\Middleware\OptimizeLoginSession::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
