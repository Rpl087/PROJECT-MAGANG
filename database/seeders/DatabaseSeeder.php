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

        User::create([
            'name'     => 'admin',
            'email'    => 'admin@bpsdmp.go.id',
            'password' => Hash::make('AdreeKosanto#'),
        ]);

        User::create([
            'name'     => 'admin2',
            'email'    => 'admin2@bpsdmp.go.id',
            'password' => Hash::make('ChrisMon_'),
        ]);

        // ── User pengujian (hapus setelah selesai testing) ──
        User::create([
            'name'     => 'test',
            'email'    => 'test@bpsdmp.go.id',
            'password' => Hash::make('test123'),
        ]);
    }
}
