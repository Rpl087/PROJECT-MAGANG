<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: MaintenanceOnly
 *
 * Izinkan akses HANYA untuk user dengan role 'maintenance'.
 * Admin biasa TIDAK dapat mengakses route yang dilindungi middleware ini.
 *
 * Gunakan pada route /maintenance/* agar panel maintenance tidak dapat
 * diakses oleh admin biasa, bahkan jika mereka mengetahui URL-nya.
 */
class MaintenanceOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isMaintenance()) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk akun maintenance.');
        }

        return $next($request);
    }
}
