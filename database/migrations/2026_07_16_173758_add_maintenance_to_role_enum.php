<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan nilai 'maintenance' ke enum role pada tabel users.
     * Akun maintenance adalah akun statis sistem yang tidak bisa diubah.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'internship', 'maintenance') NOT NULL DEFAULT 'admin'");
    }

    /**
     * Kembalikan ke enum semula (tanpa 'maintenance').
     * Pastikan tidak ada user dengan role='maintenance' sebelum rollback.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'internship') NOT NULL DEFAULT 'admin'");
    }
};
