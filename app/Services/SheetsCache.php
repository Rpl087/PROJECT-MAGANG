<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * ============================================================
 * SHEETS CACHE SERVICE
 * ============================================================
 *
 * Mengelola caching data dari Google Sheets agar tidak setiap
 * request melakukan API call ke Google (mengurangi latensi & kuota API).
 *
 * STRATEGI CACHING:
 *   - User biasa (internship): cache normal dengan TTL_TABLE (5 menit)
 *   - Admin: cache mikro terpisah dengan TTL_ADMIN_MICRO (15 detik)
 *     agar admin melihat data lebih segar setelah melakukan perubahan.
 *     Cache admin menulis ulang cache normal setiap refresh.
 *
 * KEY CACHE:
 *   - 'sheets.surat-masuk'  → data tabel surat masuk (dbSM)
 *   - 'sheets.surat-keluar' → data tabel surat keluar (dbSK)
 *   - 'sheets.dashboard'    → data statistik dashboard
 *   - 'sheets.report'       → data laporan lengkap
 *   - 'admin.micro.*'       → prefix cache khusus admin (TTL pendek)
 *
 * INVALIDASI CACHE:
 *   - forgetSuratMasuk()  : hapus cache SM + dashboard + report
 *   - forgetSuratKeluar() : hapus cache SK + dashboard + report
 *   - forgetAll()         : hapus semua cache sekaligus
 *   Dipanggil otomatis setiap store/update/destroy data.
 *
 * BACKEND CACHE:
 *   - Menggunakan driver dari config/cache.php (default: file)
 *   - Untuk performa lebih baik, ganti ke Redis atau Memcached di .env
 *     CACHE_DRIVER=redis
 */
class SheetsCache
{

    // Durasi cache data tabel (surat masuk/keluar) dalam detik (1 menit)
    public const TTL_TABLE = 60;

    // Durasi cache dashboard dan report dalam detik (1 menit)
    public const TTL_DASHBOARD = 60;

    // Durasi cache mikro khusus admin (30 detik agar seimbang antara kesegaran data dan kecepatan)
    public const TTL_ADMIN_MICRO = 30;

    public const KEY_SURAT_MASUK  = 'sheets.surat-masuk';
    public const KEY_SURAT_KELUAR = 'sheets.surat-keluar';
    public const KEY_DASHBOARD    = 'sheets.dashboard';
    public const KEY_REPORT       = 'sheets.report';

    /**
     * Ambil data dari cache, atau jalankan $callback jika cache kosong.
     *
     * Jika user adalah admin, gunakan cache mikro (TTL pendek) yang
     * juga memperbarui cache normal setiap kali diakses.
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            // Gunakan konstanta langsung — env() tidak bekerja setelah config:cache dijalankan
            return Cache::remember('admin.micro.' . $key, self::TTL_ADMIN_MICRO, function () use ($key, $ttl, $callback) {
                $data = $callback();
                Cache::put($key, $data, $ttl);
                return $data;
            });
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Menghapus satu key cache beserta versi cache mikro admin-nya.
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
        Cache::forget('admin.micro.' . $key);
    }

    /**
     * Hapus cache yang berkaitan dengan surat masuk.
     * Juga menghapus dashboard & report karena keduanya bergantung pada data SM.
     */
    public static function forgetSuratMasuk(): void
    {
        self::forget(self::KEY_SURAT_MASUK);
        self::forget(self::KEY_DASHBOARD);
        self::forget(self::KEY_REPORT);
    }

    /**
     * Hapus cache yang berkaitan dengan surat keluar.
     * Juga menghapus dashboard & report karena keduanya bergantung pada data SK.
     */
    public static function forgetSuratKeluar(): void
    {
        self::forget(self::KEY_SURAT_KELUAR);
        self::forget(self::KEY_DASHBOARD);
        self::forget(self::KEY_REPORT);
    }

    /**
     * Hapus SEMUA cache Sheets sekaligus (digunakan saat storage:clean dijalankan).
     */
    public static function forgetAll(): void
    {
        self::forget(self::KEY_SURAT_MASUK);
        self::forget(self::KEY_SURAT_KELUAR);
        self::forget(self::KEY_DASHBOARD);
        self::forget(self::KEY_REPORT);
    }
}
