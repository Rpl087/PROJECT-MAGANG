<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * MIDDLEWARE: UpdateUserLastSeen
 * ============================================================
 *
 * Mencatat waktu terakhir user aktif menggunakan aplikasi.
 * Digunakan oleh admin untuk memantau aktivitas user di halaman manajemen user.
 *
 * CARA KERJA:
 *   - Berjalan pada setiap request yang melewatinya.
 *   - Hanya memperbarui kolom 'last_seen_at' jika:
 *       a) User sudah login, DAN
 *       b) Sudah lebih dari 60 detik sejak update terakhir.
 *     Ini untuk menghindari terlalu banyak query DB pada setiap request.
 *
 * KOLOM DATABASE:
 *   - Tabel: users
 *   - Kolom: last_seen_at (datetime, nullable)
 *
 * CATATAN:
 *   - Menggunakan forceFill() agar kolom yang tidak ada di $fillable
 *     tetap bisa diisi (last_seen_at mungkin tidak selalu di $fillable).
 *   - Interval 60 detik bisa diubah sesuai kebutuhan monitoring.
 */
class UpdateUserLastSeen
{

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Update last_seen_at maksimal setiap 60 detik untuk efisiensi query DB
            if (!$user->last_seen_at || $user->last_seen_at->diffInSeconds(now()) >= 60) {
                $user->forceFill([
                    'last_seen_at' => now(),
                ])->save();
            }
        }

        return $next($request);
    }
}
