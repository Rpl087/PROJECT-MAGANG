<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah kolom visible_to dari TEXT ke JSON agar whereJsonContains berjalan
     * optimal di MySQL. Nilai yang sudah ada tetap valid karena formatnya sudah JSON.
     */
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('visible_to')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->text('visible_to')->nullable()->change();
        });
    }
};
