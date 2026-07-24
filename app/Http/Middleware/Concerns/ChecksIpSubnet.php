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
     * Cek apakah sebuah IP berada dalam range subnet CIDR (IPv4 only).
     *
     * @param  string $ip    IP address yang akan dicek
     * @param  string $cidr  Subnet dalam notasi CIDR (mis. "192.168.252.0/22")
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
