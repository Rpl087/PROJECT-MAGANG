<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intranet Mode
    |--------------------------------------------------------------------------
    |
    | Jika true, akses ke route tertentu akan dibatasi berdasarkan subnet
    | jaringan. Set INTRANET_ENABLED=true di .env untuk mengaktifkan.
    |
    */
    'intranet_enabled' => env('INTRANET_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Internet Access (Dual Access Mode)
    |--------------------------------------------------------------------------
    |
    | Jika true, pengguna dari IP publik (internet di luar kantor) dapat
    | mengakses aplikasi meskipun INTRANET_ENABLED=true. Sistem akan
    | mengenali IP publik dan melewati batasan subnet lokal secara otomatis.
    |
    | Syarat keamanan:
    |   - Pengguna internet tetap WAJIB login (autentikasi normal)
    |   - Session & CSRF protection tetap aktif
    |   - Akses admin tetap dikontrol oleh middleware AdminOnly
    |
    | Set INTERNET_ACCESS_ENABLED=false jika ingin MENUTUP akses internet
    | sepenuhnya (aplikasi hanya bisa diakses dari dalam jaringan kantor).
    |
    */
    'internet_access_enabled' => env('INTERNET_ACCESS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Internal Network Subnet
    |--------------------------------------------------------------------------
    |
    | Subnet CIDR jaringan WiFi internal kantor (khusus staff/admin).
    | Hanya IP dari subnet ini yang diizinkan melakukan operasi write.
    |
    */
    'internal_subnet' => env('INTERNAL_NETWORK_SUBNET', '192.168.248.0/22'),

    /*
    |--------------------------------------------------------------------------
    | Guest Network Subnet
    |--------------------------------------------------------------------------
    |
    | Subnet CIDR jaringan WiFi tamu kantor.
    | IP dari subnet ini hanya boleh mengakses halaman read-only (/tamu).
    |
    */
    'guest_subnet' => env('GUEST_NETWORK_SUBNET', '192.168.252.0/22'),

];
