<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ChecksIpSubnet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * MIDDLEWARE: GuestNetwork
 * ============================================================
 *
 * Mengizinkan akses ke halaman /tamu/ (read-only) bagi pegawai
 * yang berada di jaringan kantor TANPA perlu login.
 * Digunakan alias: 'guest_network' (didaftarkan di bootstrap/app.php)
 *
 * PERBEDAAN dengan InternalNetworkOnly:
 *   - InternalNetworkOnly: HANYA subnet internal (untuk admin & write)
 *   - GuestNetwork: subnet internal ATAU subnet guest (untuk baca saja)
 *
 * LOGIKA:
 *   1. Jika INTRANET_ENABLED=false → semua IP diizinkan.
 *   2. Jika localhost → diizinkan.
 *   3. Jika IP ada di internal_subnet ATAU guest_subnet → diizinkan.
 *   4. Selainnya → abort 403.
 *
 * KONFIGURASI (via .env):
 *   GUEST_NETWORK_SUBNET="192.168.248.0/22"
 *   INTERNAL_NETWORK_SUBNET="192.168.252.0/22"
 *
 * SKENARIO PENGGUNAAN:
 *   - Pegawai kantor di PC umum bisa cek arsip tanpa login.
 *   - Tamu di WiFi tamu (guest VLAN) dapat akses read-only.
 *   - Jika tidak ada VLAN terpisah, guest_subnet bisa diisi sama
 *     dengan internal_subnet.
 */
class GuestNetwork
{
    use ChecksIpSubnet;

    public function handle(Request $request, Closure $next): Response
    {
        // Jika intranet mode tidak aktif, semua IP diperbolehkan
        if (!config('network.intranet_enabled', false)) {
            return $next($request);
        }

        $clientIp       = $request->ip();
        $internalSubnet = config('network.internal_subnet', '192.168.252.0/22');
        $guestSubnet    = config('network.guest_subnet',    '10.0.0.0/24');

        // Akses lokal selalu diizinkan
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        // Boleh akses jika IP ada di jaringan internal ATAU jaringan tamu
        if ($this->ipInSubnet($clientIp, $internalSubnet) ||
            $this->ipInSubnet($clientIp, $guestSubnet)) {
            return $next($request);
        }

        abort(403, 'Halaman ini hanya dapat diakses dari jaringan kantor.');
    }
}
