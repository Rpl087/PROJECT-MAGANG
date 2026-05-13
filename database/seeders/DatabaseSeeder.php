<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Hanya 2 akun admin yang berwenang mengelola sistem arsip BPSDMP.
     */
    public function run(): void
    {
        // Hapus semua user lama sebelum seed ulang
        User::truncate();

        // ── Admin utama ──────────────────────────────────────────────────────
        User::create([
            'name'     => 'Andree Kosanto',
            'email'    => 'admin@bpsdmp.go.id',
            'password' => Hash::make('AndreeKosanto#'),
            'role'     => 'admin',
        ]);

        // ── User Internship (hanya bisa input form surat masuk & keluar) ─────
        User::create([
            'name'     => 'internship',
            'email'    => 'internship@bpsdmp.go.id',
            'password' => Hash::make('internship_BPSDMP'),
            'role'     => 'internship',
        ]);

        // ── User pengujian (hapus setelah selesai testing) ──
        User::create([
            'name'     => 'test',
            'email'    => 'test@bpsdmp.go.id',
            'password' => Hash::make('test123'),
            'role'     => 'admin',
        ]);
    }
}
