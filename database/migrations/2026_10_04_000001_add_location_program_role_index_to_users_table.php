<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Count DPL/mahasiswa per lokasi (LokasiProgramSummary) cukup dari index; index tunggal location_program tercakup prefiks komposit
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['location_program', 'role']);
            $table->dropIndex(['location_program']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('location_program');
            $table->dropIndex(['location_program', 'role']);
        });
    }
};
