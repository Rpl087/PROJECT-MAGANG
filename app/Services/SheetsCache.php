<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SheetsCache
{

    public const TTL_TABLE = 60;

    public const TTL_DASHBOARD = 60;

    public const TTL_ADMIN_MICRO = 30;

    public const KEY_SURAT_MASUK  = 'sheets.surat-masuk';
    public const KEY_SURAT_KELUAR = 'sheets.surat-keluar';
    public const KEY_DASHBOARD    = 'sheets.dashboard';
    public const KEY_REPORT       = 'sheets.report';

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasAdminAccess())) {
            return Cache::remember('admin.micro.' . $key, self::TTL_ADMIN_MICRO, function () use ($key, $ttl, $callback) {
                $data = $callback();
                Cache::put($key, $data, $ttl);
                return $data;
            });
        }

        return Cache::remember($key, $ttl, $callback);
    }

    public static function forget(string $key): void
    {
        Cache::forget($key);
        Cache::forget('admin.micro.' . $key);
    }

    public static function forgetSuratMasuk(): void
    {
        self::forget(self::KEY_SURAT_MASUK);
        self::forget(self::KEY_DASHBOARD);
        self::forget(self::KEY_REPORT);
    }

    public static function forgetSuratKeluar(): void
    {
        self::forget(self::KEY_SURAT_KELUAR);
        self::forget(self::KEY_DASHBOARD);
        self::forget(self::KEY_REPORT);
    }

    public static function forgetAll(): void
    {
        self::forget(self::KEY_SURAT_MASUK);
        self::forget(self::KEY_SURAT_KELUAR);
        self::forget(self::KEY_DASHBOARD);
        self::forget(self::KEY_REPORT);
    }
}