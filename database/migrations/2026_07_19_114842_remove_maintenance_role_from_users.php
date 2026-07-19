<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hapus role 'maintenance' dari enum users.role.
     * Sebelum mengubah enum, pastikan tidak ada user dengan role='maintenance'.
     */
    public function up(): void
    {
        // Hapus semua user dengan role maintenance
        DB::table('users')->where('role', 'maintenance')->delete();

        // Ubah enum role menjadi hanya 'admin' dan 'internship'
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'internship') NOT NULL DEFAULT 'admin'");
    }

    /**
     * Kembalikan enum ke kondisi sebelumnya (dengan 'maintenance').
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'internship', 'maintenance') NOT NULL DEFAULT 'admin'");
    }
};
