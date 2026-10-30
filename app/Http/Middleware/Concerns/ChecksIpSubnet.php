<?php

namespace App\Http\Middleware\Concerns;

trait ChecksIpSubnet
{

    private function ipInSubnet(string $ip, string $cidr): bool
    {
        if (str_contains($cidr, ',')) {
            foreach (explode(',', $cidr) as $singleCidr) {
                $trimmed = trim($singleCidr);
                if ($trimmed !== '' && $this->ipInSubnet($ip, $trimmed)) {
                    return true;
                }
            }
            return false;
        }

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
