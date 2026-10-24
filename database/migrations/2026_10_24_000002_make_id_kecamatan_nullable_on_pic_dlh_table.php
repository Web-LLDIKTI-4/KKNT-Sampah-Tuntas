<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FK di-drop dulu agar MySQL/MariaDB mengizinkan perubahan kolom
        Schema::table('pic_dlh', function (Blueprint $table) {
            $table->dropForeign(['id_kecamatan']);
        });

        Schema::table('pic_dlh', function (Blueprint $table) {
            $table->uuid('id_kecamatan')->nullable()->change();
            $table->foreign('id_kecamatan')
                ->references('id_kecamatan')
                ->on('kecamatan')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $nullCount = DB::table('pic_dlh')->whereNull('id_kecamatan')->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                "Rollback dibatalkan: {$nullCount} baris pic_dlh masih memiliki id_kecamatan NULL. Isi kecamatan baris tersebut terlebih dahulu."
            );
        }

        Schema::table('pic_dlh', function (Blueprint $table) {
            $table->dropForeign(['id_kecamatan']);
        });

        Schema::table('pic_dlh', function (Blueprint $table) {
            $table->uuid('id_kecamatan')->nullable(false)->change();
            $table->foreign('id_kecamatan')
                ->references('id_kecamatan')
                ->on('kecamatan')
                ->restrictOnDelete();
        });
    }
};
