<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Izinkan akses hanya untuk user dengan role 'admin' atau 'maintenance'.
     * User 'internship' akan mendapat 403.
     *
     * Akun maintenance memiliki akses penuh ke semua fitur admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->hasAdminAccess()) {
            abort(403, 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
