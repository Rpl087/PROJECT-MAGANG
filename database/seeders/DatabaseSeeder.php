<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Catatan penting:
     *   - Akun 'maintenance' TIDAK pernah dihapus oleh seeder ini.
     *   - Akun admin dan internship dibuat ulang jika belum ada.
     *   - Menu items default dibuat hanya jika tabel kosong.
     */
    public function run(): void
    {
        // ── Hapus hanya akun non-maintenance ──────────────────────────────────
        User::where('role', '!=', 'maintenance')->delete();

        // ── Admin utama ───────────────────────────────────────────────────────
        User::firstOrCreate(
            ['name' => 'Admin'],
            [
                'email'    => 'admin@bpsdmp.go.id',
                'password' => Hash::make('admin_BPSDMP26'),
                'role'     => 'admin',
            ]
        );

        // ── User Internship ───────────────────────────────────────────────────
        User::firstOrCreate(
            ['name' => 'internship'],
            [
                'email'    => 'internship@bpsdmp.go.id',
                'password' => Hash::make('internship_BPSDMP'),
                'role'     => 'internship',
            ]
        );

        // ── Akun Maintenance (statis — tidak bisa diubah via panel) ───────────
        // Password di-manage via .env: MAINTENANCE_PASSWORD
        User::updateOrCreate(
            ['role' => 'maintenance'],
            [
                'name'     => config('app.maintenance_username', 'Maintenance_BPSDMP_26!'),
                'email'    => 'maintenance@bpsdmp.local',
                'password' => Hash::make(config('app.maintenance_password', 'Maintenance_BPSDMP_26!')),
                'role'     => 'maintenance',
            ]
        );

        // ── Menu Items Default (hanya jika tabel kosong) ──────────────────────
        if (MenuItem::count() === 0) {
            $menus = [
                [
                    'label'         => 'Dashboard',
                    'route_name'    => 'dashboard.index',
                    'icon'          => 'grid',
                    'visible_to'    => ['admin', 'maintenance'],
                    'sort_order'    => 1,
                    'is_active'     => true,
                    'confirm_title' => 'Buka Dashboard?',
                    'confirm_desc'  => 'Anda akan diarahkan ke halaman Dashboard. Pastikan semua perubahan sudah tersimpan sebelum berpindah.',
                ],
                [
                    'label'         => 'Report Rekap',
                    'route_name'    => 'dashboard.report',
                    'icon'          => 'file-text',
                    'visible_to'    => ['admin', 'maintenance'],
                    'sort_order'    => 2,
                    'is_active'     => true,
                    'confirm_title' => 'Buka Report Rekap?',
                    'confirm_desc'  => 'Anda akan diarahkan ke halaman Report Rekap. Pastikan semua perubahan sudah tersimpan sebelum berpindah.',
                ],
                [
                    'label'         => 'Surat Masuk',
                    'route_name'    => 'arsip.index',
                    'icon'          => 'inbox',
                    'visible_to'    => ['admin', 'internship', 'maintenance'],
                    'sort_order'    => 3,
                    'is_active'     => true,
                    'confirm_title' => null,
                    'confirm_desc'  => null,
                ],
                [
                    'label'         => 'Surat Keluar',
                    'route_name'    => 'surat-keluar.index',
                    'icon'          => 'send',
                    'visible_to'    => ['admin', 'internship', 'maintenance'],
                    'sort_order'    => 4,
                    'is_active'     => true,
                    'confirm_title' => null,
                    'confirm_desc'  => null,
                ],
                [
                    'label'         => 'Kelola Pengguna',
                    'route_name'    => 'admin.users.index',
                    'icon'          => 'users',
                    'visible_to'    => ['admin', 'maintenance'],
                    'sort_order'    => 5,
                    'is_active'     => true,
                    'confirm_title' => null,
                    'confirm_desc'  => null,
                ],
                [
                    'label'         => 'Panel Maintenance',
                    'route_name'    => 'maintenance.index',
                    'icon'          => 'settings',
                    'visible_to'    => ['maintenance'],
                    'sort_order'    => 10,
                    'is_active'     => true,
                    'confirm_title' => null,
                    'confirm_desc'  => null,
                ],
            ];

            foreach ($menus as $menu) {
                MenuItem::create($menu);
            }
        }
    }
}
