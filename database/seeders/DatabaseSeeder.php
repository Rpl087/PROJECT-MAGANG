<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Akun admin dan internship untuk sistem arsip BPSDMP.
     */
    public function run(): void
    {
        // Hapus semua user lama sebelum seed ulang
        User::truncate();

        // ── Admin utama ──────────────────────────────────────────────────────
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@bpsdmp.go.id',
            'password' => Hash::make('admin_BPSDMP26'),
            'role'     => 'admin',
        ]);

        // ── User Internship (hanya bisa input form surat masuk & keluar) ─────
        User::create([
            'name'     => 'internship',
            'email'    => 'internship@bpsdmp.go.id',
            'password' => Hash::make('internship_BPSDMP'),
            'role'     => 'internship',
        ]);
    }
}
