<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel menu_items menyimpan item navigasi sidebar yang dapat dikelola
     * secara dinamis oleh akun maintenance.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);                          // Nama tampilan menu
            $table->string('route_name', 100)->nullable();         // Nama route Laravel
            $table->string('icon', 30)->default('file');           // Identifier ikon (grid, inbox, send, dll)
            $table->text('visible_to')->nullable();                // JSON: Role yang dapat melihat menu ini (disimpan sebagai text)
            $table->integer('sort_order')->default(0);             // Urutan tampil
            $table->boolean('is_active')->default(true);           // Aktif/nonaktif
            $table->string('confirm_title', 150)->nullable();      // Judul modal konfirmasi navigasi
            $table->string('confirm_desc', 300)->nullable();       // Deskripsi modal konfirmasi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
