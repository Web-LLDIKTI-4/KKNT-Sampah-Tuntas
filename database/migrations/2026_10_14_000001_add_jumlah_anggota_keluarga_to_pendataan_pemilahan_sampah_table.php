<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable: data lama belum punya nilai
        Schema::table('pendataan_pemilahan_sampah', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_anggota_keluarga')->nullable()->after('nama_kepala_keluarga');
        });
    }

    public function down(): void
    {
        Schema::table('pendataan_pemilahan_sampah', function (Blueprint $table) {
            $table->dropColumn('jumlah_anggota_keluarga');
        });
    }
};
