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
        // Percayai semua proxy (termasuk Ngrok) agar HTTPS terdeteksi dengan benar
        $middleware->trustProxies(at: '*');

        // ── Security Headers — berjalan di semua request ─────────────────────
        // Menambahkan HTTP security headers untuk proteksi dari XSS, Clickjacking,
        // MIME sniffing, dan serangan lainnya
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ── Middleware Jaringan Intranet ─────────────────────────────────────
        // Izinkan akses ke route tamu dari WiFi tamu atau internal
        $middleware->alias([
            'guest_network'   => \App\Http\Middleware\GuestNetwork::class,
            'internal_only'   => \App\Http\Middleware\InternalNetworkOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
