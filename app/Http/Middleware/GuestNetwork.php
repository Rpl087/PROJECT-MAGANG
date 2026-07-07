<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: GuestNetwork
 *
 * Mengizinkan akses ke route read-only khusus tamu HANYA dari:
 *   1. Jaringan WiFi Tamu  (GUEST_NETWORK_SUBNET)
 *   2. Jaringan WiFi Internal (INTERNAL_NETWORK_SUBNET) — staff boleh preview halaman tamu
 *
 * Jika request berasal dari luar kedua jaringan tersebut (mis. internet publik),
 * akses akan ditolak dengan 403.
 */
class GuestNetwork
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp        = $request->ip();
        $internalSubnet  = env('INTERNAL_NETWORK_SUBNET', '192.168.248.0/22');
        $guestSubnet     = env('GUEST_NETWORK_SUBNET',    '192.168.252.0/22');

        // Izinkan jika dari jaringan tamu atau internal
        if ($this->ipInSubnet($clientIp, $guestSubnet) || $this->ipInSubnet($clientIp, $internalSubnet)) {
            return $next($request);
        }

        // Juga izinkan localhost untuk development
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        abort(403, 'Halaman ini hanya dapat diakses dari jaringan kantor.');
    }

    /**
     * Cek apakah sebuah IP berada dalam range subnet CIDR.
     *
     * @param  string $ip      IP address yang akan dicek (mis. "192.168.252.85")
     * @param  string $cidr    Subnet dalam notasi CIDR (mis. "192.168.252.0/22")
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
