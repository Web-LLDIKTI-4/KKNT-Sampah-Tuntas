<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mahasiswa', 'dpl', 'pt', 'kepala'])->nullable()->default('mahasiswa')->change();
        });

        Schema::table('kpi_target', function (Blueprint $table) {
            $table->decimal('target', 12, 2)->nullable()->after('nama_kpitarget');
            $table->string('satuan', 50)->nullable()->after('target');
        });
        DB::table('kpi_target')->update(['target' => DB::raw('CAST(persen AS DECIMAL(12,2))'), 'satuan' => '%']);
        Schema::table('kpi_target', function (Blueprint $table) {
            $table->dropColumn('persen');
        });

        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->decimal('realisasi', 12, 2)->nullable()->after('id_target');
            $table->string('satuan', 50)->nullable()->after('realisasi');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->dropColumn(['realisasi', 'satuan']);
        });

        Schema::table('kpi_target', function (Blueprint $table) {
            $table->string('persen', 10)->nullable()->after('nama_kpitarget');
        });
        DB::table('kpi_target')->update(['persen' => DB::raw('CAST(target AS CHAR)')]);
        Schema::table('kpi_target', function (Blueprint $table) {
            $table->dropColumn(['target', 'satuan']);
        });

        DB::table('users')->where('role', 'kepala')->update(['role' => null]);
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mahasiswa', 'dpl', 'pt'])->nullable()->default('mahasiswa')->change();
        });
    }
};
