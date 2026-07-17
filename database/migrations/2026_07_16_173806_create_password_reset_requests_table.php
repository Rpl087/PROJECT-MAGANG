<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel password_reset_requests mencatat permintaan reset password dari user.
     * Admin/maintenance dapat melihat dan menindaklanjuti permintaan ini.
     */
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);         // Username yang meminta reset
            $table->string('ip_address', 45);        // IP pemohon
            $table->boolean('is_resolved')->default(false); // Sudah diselesaikan?
            $table->timestamp('resolved_at')->nullable();   // Kapan diselesaikan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
