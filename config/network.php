<?php

/**
 * ============================================================
 * KONFIGURASI JARINGAN (INTRANET & SUBNET)
 * ============================================================
 *
 * File ini mengatur pembatasan akses berbasis IP/subnet untuk aplikasi kearsipan.
 * Semua nilai diambil dari .env sehingga mudah diubah tanpa menyentuh kode.
 *
 * CARA KERJA:
 *   - Jika INTRANET_ENABLED=false  → semua middleware jaringan DINONAKTIFKAN
 *     (semua IP diizinkan). Cocok untuk pengembangan lokal atau debugging.
 *   - Jika INTRANET_ENABLED=true   → middleware aktif dan memfilter IP.
 *
 * SUBNET FORMAT: CIDR notation, contoh:
 *   - 192.168.1.0/24  → semua IP dari 192.168.1.1 s/d 192.168.1.254
 *   - 192.168.0.0/22  → mencakup 192.168.0.x – 192.168.3.x (1022 host)
 *   - Untuk mendukung BEBERAPA subnet, pisahkan dengan koma:
 *     INTERNAL_NETWORK_SUBNET="192.168.100.0/24,192.168.252.0/22"
 *
 * PEMETAAN SUBNET → MIDDLEWARE:
 *   - internal_subnet → InternalNetworkOnly (akses admin & write)
 *   - guest_subnet    → GuestNetwork (akses halaman /tamu/ baca saja)
 *
 * DEPLOYMENT NYATA (KANTOR):
 *   - Sesuaikan subnet dengan range IP LAN kantor.
 *   - Jika memakai MikroTik custom domain, pastikan IP server masuk subnet.
 *   - Untuk akses dari internet, nonaktifkan INTRANET_ENABLED atau gunakan VPN.
 */

return [

    // Aktifkan/nonaktifkan pembatasan jaringan secara keseluruhan.
    // false = semua IP bebas akses (mode development/testing)
    // true  = hanya subnet yang terdaftar yang bisa akses fitur tertentu
    'intranet_enabled' => env('INTRANET_ENABLED', false),

    // Subnet jaringan internal kantor (untuk admin & operasi tulis).
    // Mendukung satu atau beberapa subnet dipisah koma.
    // Contoh nilai .env: INTERNAL_NETWORK_SUBNET="192.168.100.0/24,192.168.252.0/22"
    'internal_subnet' => env('INTERNAL_NETWORK_SUBNET', '192.168.252.0/22'),

    // Subnet jaringan tamu (untuk halaman /tamu/ read-only).
    // Boleh sama dengan internal_subnet jika tidak ada VLAN terpisah.
    'guest_subnet' => env('GUEST_NETWORK_SUBNET', '10.0.0.0/24'),

];
