<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ChecksIpSubnet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: GuestNetwork
 *
 * Mengamankan route read-only tamu (/tamu, /tamu/surat-keluar) — hanya intranet.
 *
 * ─── MODE AKTIF (INTRANET_ENABLED=true) ──────────────────────────────────────
 *   • Localhost (127.0.0.1, ::1) → selalu diizinkan (development)
 *   • IP dari subnet internal kantor → diizinkan
 *   • IP dari subnet WiFi tamu → diizinkan (halaman tamu bersifat read-only)
 *   • Semua IP lain → DIBLOKIR (403)
 *
 * ─── MODE BYPASS (INTRANET_ENABLED=false) ────────────────────────────────────
 *   • Semua IP diizinkan (mode development / akses bebas)
 */
class GuestNetwork
{
    use ChecksIpSubnet;

    public function handle(Request $request, Closure $next): Response
    {
        // ── [BYPASS] Sistem intranet dinonaktifkan ────────────────────────────
        if (!config('network.intranet_enabled', false)) {
            return $next($request);
        }

        $clientIp       = $request->ip();
        $internalSubnet = config('network.internal_subnet', '192.168.252.0/22');
        $guestSubnet    = config('network.guest_subnet',    '10.0.0.0/24');

        // ── Selalu izinkan localhost (development) ────────────────────────────
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        // ── Izinkan dari subnet internal atau tamu ────────────────────────────
        if ($this->ipInSubnet($clientIp, $internalSubnet) ||
            $this->ipInSubnet($clientIp, $guestSubnet)) {
            return $next($request);
        }

        // ── Blokir semua IP lain ──────────────────────────────────────────────
        abort(403, 'Halaman ini hanya dapat diakses dari jaringan kantor.');
    }
}
