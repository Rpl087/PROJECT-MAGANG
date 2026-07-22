<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * - Hapus semua user lama dan buat ulang akun admin & internship default.
     */
    public function run(): void
    {
        // ── Hapus semua user lama ─────────────────────────────────────────────
        User::query()->delete();

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
            ['name' => 'Internship'],
            [
                'email'    => 'internship@bpsdmp.go.id',
                'password' => Hash::make('internship_BPSDMP'),
                'role'     => 'internship',
            ]
        );
    }
}

