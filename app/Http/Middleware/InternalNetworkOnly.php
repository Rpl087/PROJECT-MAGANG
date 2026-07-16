<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: InternalNetworkOnly
 *
 * Mengamankan route sensitif (write/delete/admin) dengan 2 mode operasi:
 *
 * ─── MODE DUAL ACCESS (INTERNET_ACCESS_ENABLED=true) ────────────────────────
 *   • IP Publik (internet luar kantor) → diizinkan melewati middleware ini.
 *     Keamanan tetap dijamin oleh lapisan autentikasi (login wajib) dan AdminOnly.
 *   • IP Lokal dari subnet internal → diizinkan (staff kantor).
 *   • IP Lokal dari subnet tamu → DIBLOKIR (403).
 *   • IP Lokal tidak dikenal → DIBLOKIR (403).
 *
 * ─── MODE INTRANET ONLY (INTERNET_ACCESS_ENABLED=false) ─────────────────────
 *   • Semua IP publik → DIBLOKIR (403). Akses hanya dari jaringan kantor.
 *   • IP Lokal dari subnet internal → diizinkan.
 *   • IP Lokal lainnya → DIBLOKIR.
 *
 * ─── MODE BYPASS (INTRANET_ENABLED=false) ────────────────────────────────────
 *   • Semua IP diizinkan (mode development / akses bebas).
 */
class InternalNetworkOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        // ── [BYPASS] Sistem intranet dinonaktifkan ────────────────────────────
        // Set INTRANET_ENABLED=false di .env untuk menonaktifkan semua pembatasan jaringan.
        if (!config('network.intranet_enabled', false)) {
            return $next($request);
        }

        $clientIp       = $request->ip();
        $internalSubnet = config('network.internal_subnet', '192.168.248.0/22');
        $guestSubnet    = config('network.guest_subnet',    '192.168.252.0/22');
        $internetEnabled = config('network.internet_access_enabled', true);

        // ── Selalu izinkan localhost (development) ────────────────────────────
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        // ── Cek apakah IP berasal dari internet publik ────────────────────────
        if ($this->isPublicIp($clientIp)) {
            if ($internetEnabled) {
                // Dual Access Mode: izinkan IP publik, keamanan dijamin oleh Auth middleware
                return $next($request);
            }
            // Intranet Only Mode: tolak semua IP publik
            abort(403, 'Akses dari internet tidak diizinkan. Aplikasi ini hanya tersedia di jaringan internal kantor.');
        }

        // ── IP lokal: terapkan aturan subnet ─────────────────────────────────

        // Blokir jika request dari jaringan tamu (meski INTERNET_ACCESS_ENABLED=true)
        if ($this->ipInSubnet($clientIp, $guestSubnet)) {
            abort(403, 'Akses ke fitur ini hanya tersedia dari jaringan internal kantor. Silakan gunakan WiFi staff.');
        }

        // Izinkan jika dari subnet internal
        if ($this->ipInSubnet($clientIp, $internalSubnet)) {
            return $next($request);
        }

        // Tolak IP lokal yang tidak dikenal di kedua subnet
        abort(403, 'Akses hanya tersedia dari jaringan internal kantor.');
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
     * @param  string $ip    IP address yang akan dicek
     * @param  string $cidr  Subnet dalam notasi CIDR (mis. "192.168.248.0/22")
     * @return bool
     */
    private function ipInSubnet(string $ip, string $cidr): bool
    {
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

        $mask = $prefix === 0 ? 0 : (~0 << (32 - $prefix));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
