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
        // ── Admin utama ───────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@bpsdmp.go.id'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin_BPSDMP26'),
                'role'     => 'admin',
            ]
        );

        // ── User Internship ───────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'internship@bpsdmp.go.id'],
            [
                'name'     => 'Internship',
                'password' => Hash::make('internship_BPSDMP'),
                'role'     => 'internship',
            ]
        );
    }
}

