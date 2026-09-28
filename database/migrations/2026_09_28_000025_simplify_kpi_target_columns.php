<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_target', function (Blueprint $table) {
            $table->renameColumn('nama_kpitarget', 'kegiatan');
        });
        Schema::table('kpi_target', function (Blueprint $table) {
            $table->dropColumn('tahapan');
        });
        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->dropColumn('tahapan');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->integer('tahapan')->nullable()->after('id_kpi');
        });
        Schema::table('kpi_target', function (Blueprint $table) {
            $table->string('tahapan', 50)->nullable()->after('id_kpi');
            $table->renameColumn('kegiatan', 'nama_kpitarget');
        });
    }
};
