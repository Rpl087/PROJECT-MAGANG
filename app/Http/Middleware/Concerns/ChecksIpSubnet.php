<?php

namespace App\Http\Middleware\Concerns;

/**
 * Trait ChecksIpSubnet
 *
 * Menyediakan method ipInSubnet() untuk validasi IP dalam range CIDR.
 * Digunakan oleh InternalNetworkOnly dan GuestNetwork middleware
 * agar tidak ada duplikasi kode.
 */
trait ChecksIpSubnet
{
    /**
     * Cek apakah sebuah IP berada dalam range subnet CIDR (IPv4 & IPv6).
     *
     * @param  string $ip    IP address yang akan dicek
     * @param  string $cidr  Subnet dalam notasi CIDR (mis. "192.168.252.0/22" atau "2001:db8::/32")
     * @return bool
     */
    private function ipInSubnet(string $ip, string $cidr): bool
    {
        // ── Handling IPv4 ───────────────────────────────────────────────────
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (!str_contains($cidr, '/')) {
                return $ip === $cidr;
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

        // ── Handling IPv6 ───────────────────────────────────────────────────
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (!str_contains($cidr, '/')) {
                return inet_pton($ip) === inet_pton($cidr);
            }

            [$subnet, $prefix] = explode('/', $cidr, 2);
            $prefix            = (int) $prefix;

            $ipBin     = inet_pton($ip);
            $subnetBin = inet_pton($subnet);

            if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== 16 || strlen($subnetBin) !== 16) {
                return false;
            }

            $bytes = (int) ($prefix / 8);
            $bits  = $prefix % 8;

            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }

            if ($bits > 0 && $bytes < 16) {
                $maskByte   = (0xFF << (8 - $bits)) & 0xFF;
                $ipByte     = ord($ipBin[$bytes]);
                $subnetByte = ord($subnetBin[$bytes]);
                return ($ipByte & $maskByte) === ($subnetByte & $maskByte);
            }

            return true;
        }

        return false;
    }
}
