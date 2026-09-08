<?php

namespace App\Http\Middleware\Concerns;

/**
 * ============================================================
 * TRAIT: ChecksIpSubnet
 * ============================================================
 *
 * Menyediakan logika pengecekan apakah suatu IP address masuk
 * dalam range subnet CIDR (IPv4 maupun IPv6).
 *
 * FITUR UTAMA:
 *   - Mendukung pengecekan BEBERAPA subnet sekaligus dengan memisahkan
 *     nilai CIDR menggunakan koma (,). Contoh: "192.168.1.0/24,10.0.0.0/8"
 *   - Mendukung IPv4 (menggunakan ip2long + bitmask)
 *   - Mendukung IPv6 (menggunakan inet_pton + perbandingan byte)
 *   - Jika CIDR tidak mengandung '/', dibandingkan sebagai IP tunggal.
 *
 * CARA PAKAI (digunakan oleh InternalNetworkOnly dan GuestNetwork):
 *   if ($this->ipInSubnet($clientIp, $subnetCidr)) { ... }
 *
 * CATATAN:
 *   - Nilai subnet berasal dari config/network.php yang dibaca dari .env
 *   - Untuk menambah subnet baru, cukup edit nilai di .env, tidak perlu
 *     mengubah kode di sini.
 */
trait ChecksIpSubnet
{

    /**
     * Mengecek apakah $ip masuk dalam satu atau beberapa subnet CIDR.
     *
     * @param string $ip   IP address client (IPv4 atau IPv6)
     * @param string $cidr Satu subnet CIDR atau beberapa dipisah koma
     * @return bool
     */
    private function ipInSubnet(string $ip, string $cidr): bool
    {
        // Jika ada koma, pecah menjadi beberapa subnet dan cek satu per satu
        if (str_contains($cidr, ',')) {
            foreach (explode(',', $cidr) as $singleCidr) {
                $trimmed = trim($singleCidr);
                if ($trimmed !== '' && $this->ipInSubnet($ip, $trimmed)) {
                    return true;
                }
            }
            return false;
        }

        // --- Pengecekan IPv4 ---
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (!str_contains($cidr, '/')) {
                return $ip === $cidr; // Pencocokan IP tunggal
            }

            [$subnet, $prefix] = explode('/', $cidr, 2);
            $prefix            = (int) $prefix;

            $ipLong     = ip2long($ip);
            $subnetLong = ip2long($subnet);

            if ($ipLong === false || $subnetLong === false) {
                return false;
            }

            // Membuat bitmask dari prefix, lalu bandingkan network address
            $mask = $prefix === 0 ? 0 : (~0 << (32 - $prefix));

            return ($ipLong & $mask) === ($subnetLong & $mask);
        }

        // --- Pengecekan IPv6 ---
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (!str_contains($cidr, '/')) {
                return inet_pton($ip) === inet_pton($cidr); // IP tunggal
            }

            [$subnet, $prefix] = explode('/', $cidr, 2);
            $prefix            = (int) $prefix;

            $ipBin     = inet_pton($ip);
            $subnetBin = inet_pton($subnet);

            if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== 16 || strlen($subnetBin) !== 16) {
                return false;
            }

            // Bandingkan byte per byte sesuai prefix
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

        return false; // Bukan IPv4 maupun IPv6 yang valid
    }
}
