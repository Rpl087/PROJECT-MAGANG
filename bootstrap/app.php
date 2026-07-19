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
        // Percayai semua proxy — diperlukan agar ngrok (IP publik) dan reverse proxy
        // intranet dapat meneruskan X-Forwarded-For / X-Forwarded-Host dengan benar.
        // Catatan: '*' aman di environment development/audit (ngrok) maupun intranet lokal.
        $middleware->trustProxies(at: '*');

        // ── Security Headers — berjalan di semua request ─────────────────────
        // Menambahkan HTTP security headers untuk proteksi dari XSS, Clickjacking,
        // MIME sniffing, dan serangan lainnya
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ── Middleware Aliases ────────────────────────────────────────────────
        $middleware->alias([
            // Izinkan akses ke route tamu dari WiFi tamu atau internal
            'guest_network'    => \App\Http\Middleware\GuestNetwork::class,
            // Batasi route write/admin ke jaringan internal saja
            'internal_only'    => \App\Http\Middleware\InternalNetworkOnly::class,
            // Blokir edit/hapus akun maintenance
            'block_maint_edit' => \App\Http\Middleware\BlockMaintenanceEdit::class,
            // Izinkan HANYA akun maintenance (bukan admin biasa)
            'maintenance_only' => \App\Http\Middleware\MaintenanceOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
