<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * MIDDLEWARE: AdminOnly
 * ============================================================
 *
 * Membatasi akses ke route tertentu HANYA untuk user dengan role 'admin'.
 * Digunakan sebagai middleware langsung (tidak ada alias khusus).
 *
 * PEMERIKSAAN:
 *   - User harus sudah login (Auth::check())
 *   - User harus memiliki role 'admin' (User::isAdmin())
 *
 * Jika gagal → abort 403. User biasa (internship) akan ditolak.
 *
 * CARA MENAMBAH ROLE BARU:
 *   1. Tambahkan nilai role baru di tabel 'users' (kolom 'role')
 *   2. Tambahkan method cek di app/Models/User.php (misal: isManager())
 *   3. Buat middleware baru atau modifikasi ini sesuai kebutuhan.
 */
class AdminOnly
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
