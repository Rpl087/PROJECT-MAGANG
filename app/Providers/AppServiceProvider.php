<?php

namespace App\Providers;

use App\Models\MenuItem;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── View Composer: injeksi menu items ke semua layout ──────────────────
        // Sidebar merender menu dari database (dikelola oleh akun maintenance).
        // Data ini tersedia di $menuItems di semua view yang menggunakan layouts.app.
        View::composer('layouts.app', function ($view) {
            try {
                $menuItems = MenuItem::active()->get();
            } catch (\Exception $e) {
                // Fallback jika tabel belum ada (saat migrasi pertama kali)
                $menuItems = collect();
            }

            $view->with('menuItems', $menuItems);
        });
    }
}
