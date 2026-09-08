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

        // BruteForceIpBan: Dicek PERTAMA sebelum middleware lain.
        // IP yang sudah di-ban mendapat 429 seketika tanpa memproses request lebih lanjut.
        $middleware->append(\App\Http\Middleware\BruteForceIpBan::class);

        // SecurityHeaders: Tambahkan HTTP security headers ke semua response.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // UpdateUserLastSeen: Perbarui kolom last_seen_at setiap 60 detik untuk user yang login.
        $middleware->appendToGroup('web', \App\Http\Middleware\UpdateUserLastSeen::class);

        $middleware->alias([

            // Alias middleware akses jaringan tamu (read-only, tanpa login)
            'guest_network'  => \App\Http\Middleware\GuestNetwork::class,

            // Alias middleware akses jaringan internal (untuk write/admin)
            'internal_only'  => \App\Http\Middleware\InternalNetworkOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })->create();

