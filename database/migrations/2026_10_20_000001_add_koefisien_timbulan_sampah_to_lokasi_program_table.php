<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable: NULL = belum diisi, data lama aman tanpa backfill
        Schema::table('lokasi_program', function (Blueprint $table) {
            $table->decimal('koefisien_timbulan_sampah', 8, 4)->nullable()->after('nama_lokasi');
        });
    }

    public function down(): void
    {
        Schema::table('lokasi_program', function (Blueprint $table) {
            $table->dropColumn('koefisien_timbulan_sampah');
        });
    }
};
