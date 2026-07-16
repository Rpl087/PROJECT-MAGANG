<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: GuestNetwork
 *
 * Mengamankan route read-only tamu (/tamu, /tamu/surat-keluar) dengan 2 mode:
 *
 * ─── MODE DUAL ACCESS (INTERNET_ACCESS_ENABLED=true) ────────────────────────
 *   • IP Publik (internet luar kantor) → diizinkan mengakses halaman tamu.
 *     Halaman tamu bersifat read-only, aman untuk diakses publik.
 *   • IP Lokal (subnet internal atau tamu) → diizinkan seperti biasa.
 *
 * ─── MODE INTRANET ONLY (INTERNET_ACCESS_ENABLED=false) ─────────────────────
 *   • Semua IP publik → DIBLOKIR (403). Halaman tamu hanya bisa dibuka
 *     dari dalam jaringan kantor.
 *   • IP Lokal (subnet internal atau tamu) → diizinkan.
 *
 * ─── MODE BYPASS (INTRANET_ENABLED=false) ────────────────────────────────────
 *   • Semua IP diizinkan (mode development / akses bebas).
 */
class GuestNetwork
{
    public function handle(Request $request, Closure $next): Response
    {
        // ── [BYPASS] Sistem intranet dinonaktifkan ────────────────────────────
        // Set INTRANET_ENABLED=false di .env untuk menonaktifkan semua pembatasan jaringan.
        if (!config('network.intranet_enabled', false)) {
            return $next($request);
        }

        $clientIp        = $request->ip();
        $internalSubnet  = config('network.internal_subnet', '192.168.248.0/22');
        $guestSubnet     = config('network.guest_subnet',    '192.168.252.0/22');
        $internetEnabled = config('network.internet_access_enabled', true);

        // ── Selalu izinkan localhost (development) ────────────────────────────
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        // ── Cek apakah IP berasal dari internet publik ────────────────────────
        if ($this->isPublicIp($clientIp)) {
            if ($internetEnabled) {
                // Dual Access Mode: halaman tamu bersifat read-only, izinkan IP publik
                return $next($request);
            }
            // Intranet Only Mode: tolak semua IP publik
            abort(403, 'Halaman ini hanya dapat diakses dari jaringan kantor.');
        }

        // ── IP lokal: izinkan dari subnet internal atau tamu ──────────────────
        if ($this->ipInSubnet($clientIp, $guestSubnet) || $this->ipInSubnet($clientIp, $internalSubnet)) {
            return $next($request);
        }

        // Tolak IP lokal yang tidak dikenal
        abort(403, 'Halaman ini hanya dapat diakses dari jaringan kantor.');
    }

    /**
     * Tentukan apakah sebuah IP adalah IP publik (bukan IP private/reserved).
     *
     * IP Private (RFC 1918):
     *   10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
     * IP Loopback:
     *   127.0.0.0/8
     * IP Link-Local:
     *   169.254.0.0/16
     *
     * Jika IP TIDAK termasuk range di atas, berarti IP publik (internet).
     *
     * @param  string $ip  IP address yang akan dicek
     * @return bool
     */
    private function isPublicIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        // filter_var dengan flag ini mengembalikan false jika IP adalah private/reserved
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Cek apakah sebuah IP berada dalam range subnet CIDR.
     *
     * @param  string $ip    IP address yang akan dicek (mis. "192.168.252.85")
     * @param  string $cidr  Subnet dalam notasi CIDR (mis. "192.168.252.0/22")
     * @return bool
     */
    private function ipInSubnet(string $ip, string $cidr): bool
    {
        // Hanya mendukung IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $prefix            = (int) $prefix;

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        // Buat mask: misal prefix=22 → mask = 0xFFFFFC00
        $mask = $prefix === 0 ? 0 : (~0 << (32 - $prefix));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
