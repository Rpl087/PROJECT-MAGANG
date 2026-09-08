<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ChecksIpSubnet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * MIDDLEWARE: InternalNetworkOnly
 * ============================================================
 *
 * Membatasi akses ke route tertentu HANYA dari jaringan internal kantor.
 * Digunakan alias: 'internal_only' (didaftarkan di bootstrap/app.php)
 *
 * YANG DILINDUNGI:
 *   - POST /arsip         (tambah surat masuk)
 *   - POST /surat-keluar  (tambah surat keluar)
 *   - Semua route grup admin (edit, hapus, dashboard)
 *
 * LOGIKA PENGECEKAN:
 *   1. Jika INTRANET_ENABLED=false → lewati semua cek, izinkan akses.
 *   2. Jika IP adalah localhost (127.0.0.1, ::1) → selalu izinkan.
 *   3. Jika IP masuk dalam internal_subnet → izinkan.
 *   4. Selainnya → abort 403.
 *
 * KONFIGURASI (via .env):
 *   INTRANET_ENABLED=true
 *   INTERNAL_NETWORK_SUBNET="192.168.100.0/24,192.168.252.0/22"
 *
 * TROUBLESHOOTING:
 *   - Jika akses tiba-tiba 403 padahal IP sudah benar, cek apakah IP
 *     client ter-proxy/masquerade sehingga $request->ip() berbeda.
 *   - Laragon/Apache reverse proxy mungkin meneruskan IP asli via
 *     X-Forwarded-For (konfigurasi TrustedProxies jika diperlukan).
 */
class InternalNetworkOnly
{
    use ChecksIpSubnet;

    public function handle(Request $request, Closure $next): Response
    {
        // Jika intranet mode tidak aktif, semua IP diperbolehkan masuk
        if (!config('network.intranet_enabled', false)) {
            return $next($request);
        }

        $clientIp       = $request->ip();
        $internalSubnet = config('network.internal_subnet', '192.168.252.0/22');

        // Akses lokal (server itu sendiri) selalu diizinkan
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        // Cek apakah IP client masuk subnet internal (mendukung multi-subnet)
        if ($this->ipInSubnet($clientIp, $internalSubnet)) {
            return $next($request);
        }

        abort(403, 'Akses hanya tersedia dari jaringan internal kantor.');
    }
}
