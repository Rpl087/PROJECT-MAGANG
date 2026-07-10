<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * SheetsCache
 *
 * Centralized helper untuk caching data Google Sheets.
 * Mencegah pemanggilan Google API berulang pada setiap request,
 * sehingga loading halaman turun dari beberapa menit menjadi < 1 detik.
 *
 * Cache Keys:
 *   sheets.surat-masuk   → data sheet "SURAT MASUK!A:Q"
 *   sheets.surat-keluar  → data sheet "SURAT KELUAR!A:Q"
 *   sheets.dbsm          → data sheet "dbSM!A:R"
 *   sheets.dbsk          → data sheet "dbSK!A:R" (opsional, jika dibutuhkan)
 *
 * TTL default: 5 menit (300 detik)
 * Cache di-invalidate otomatis setelah store / update / destroy.
 */
class SheetsCache
{
    /** TTL dalam detik untuk data tabel (5 menit) */
    public const TTL_TABLE = 300;

    /** TTL dalam detik untuk data dashboard (5 menit) */
    public const TTL_DASHBOARD = 300;

    // ── Cache Keys ────────────────────────────────────────────────────────────

    public const KEY_SURAT_MASUK  = 'sheets.surat-masuk';
    public const KEY_SURAT_KELUAR = 'sheets.surat-keluar';
    public const KEY_DBSM         = 'sheets.dbsm';
    public const KEY_DASHBOARD    = 'sheets.dashboard';
    public const KEY_REPORT       = 'sheets.report';

    /**
     * Ambil data dari cache; jika miss, jalankan $callback dan simpan hasilnya.
     *
     * @param  string   $key      Cache key
     * @param  int      $ttl      Time-to-live (detik)
     * @param  callable $callback Fungsi yang memanggil Google Sheets API
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Hapus satu cache key (invalidasi setelah write).
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Hapus semua cache terkait Surat Masuk.
     * Dipanggil setelah store / update / destroy pada ArsipController.
     */
    public static function forgetSuratMasuk(): void
    {
        Cache::forget(self::KEY_SURAT_MASUK);
        Cache::forget(self::KEY_DBSM);
        Cache::forget(self::KEY_DASHBOARD);
        Cache::forget(self::KEY_REPORT);
    }

    /**
     * Hapus semua cache terkait Surat Keluar.
     * Dipanggil setelah store / update / destroy pada SuratKeluarController.
     */
    public static function forgetSuratKeluar(): void
    {
        Cache::forget(self::KEY_SURAT_KELUAR);
        Cache::forget(self::KEY_DASHBOARD);
        Cache::forget(self::KEY_REPORT);
    }

    /**
     * Hapus seluruh cache Google Sheets (nuclear option).
     */
    public static function forgetAll(): void
    {
        Cache::forget(self::KEY_SURAT_MASUK);
        Cache::forget(self::KEY_SURAT_KELUAR);
        Cache::forget(self::KEY_DBSM);
        Cache::forget(self::KEY_DASHBOARD);
        Cache::forget(self::KEY_REPORT);
    }
}
