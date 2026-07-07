<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: InternalNetworkOnly
 *
 * Memblokir akses ke route sensitif (write/delete/admin) jika request
 * berasal dari subnet WiFi Tamu. Hanya jaringan internal yang diizinkan.
 *
 * Ini adalah lapisan keamanan tambahan di atas autentikasi,
 * sehingga tamu yang somehow memiliki kredensial tetap tidak bisa
 * melakukan operasi write dari jaringan tamu.
 */
class InternalNetworkOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp       = $request->ip();
        $internalSubnet = env('INTERNAL_NETWORK_SUBNET', '192.168.248.0/22');
        $guestSubnet    = env('GUEST_NETWORK_SUBNET',    '192.168.252.0/22');

        // Blokir jika request dari jaringan tamu
        if ($this->ipInSubnet($clientIp, $guestSubnet)) {
            abort(403, 'Akses ke fitur ini hanya tersedia dari jaringan internal kantor. Silakan gunakan WiFi staff.');
        }

        // Izinkan localhost (development)
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        // Izinkan jaringan internal
        if ($this->ipInSubnet($clientIp, $internalSubnet)) {
            return $next($request);
        }

        // Tolak IP yang tidak dikenal
        abort(403, 'Akses hanya tersedia dari jaringan internal kantor.');
    }

    /**
     * Cek apakah sebuah IP berada dalam range subnet CIDR.
     *
     * @param  string $ip      IP address yang akan dicek
     * @param  string $cidr    Subnet dalam notasi CIDR (mis. "192.168.248.0/22")
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
