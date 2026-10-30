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

        $trustedProxies = env('TRUSTED_PROXIES');
        $middleware->trustProxies(at: $trustedProxies ? array_map('trim', explode(',', $trustedProxies)) : ['127.0.0.1', '::1']);

        $middleware->append(\App\Http\Middleware\BruteForceIpBan::class);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->appendToGroup('web', \App\Http\Middleware\UpdateUserLastSeen::class);

        $middleware->alias([

            'guest_network'  => \App\Http\Middleware\GuestNetwork::class,

            'internal_only'  => \App\Http\Middleware\InternalNetworkOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })->create();

