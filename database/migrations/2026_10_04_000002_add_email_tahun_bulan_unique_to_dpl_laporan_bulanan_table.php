<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Satu laporan per DPL per bulan; NOT NULL wajib karena UNIQUE MySQL mengabaikan baris ber-NULL. Index tunggal email tercakup prefiks unique
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dpl_laporan_bulanan', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->year('tahun')->nullable(false)->change();
            $table->integer('bulan')->nullable(false)->change();
            $table->unique(['email', 'tahun', 'bulan']);
            $table->dropIndex(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('dpl_laporan_bulanan', function (Blueprint $table) {
            $table->index('email');
            $table->dropUnique(['email', 'tahun', 'bulan']);
            $table->string('email')->nullable()->change();
            $table->year('tahun')->nullable()->change();
            $table->integer('bulan')->nullable()->change();
        });
    }
};
