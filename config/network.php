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
