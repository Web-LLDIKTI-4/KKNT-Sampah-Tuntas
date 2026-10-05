<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_sampah', function (Blueprint $table) {
            $table->uuid('id_sampah')->primary();
            $table->uuid('id_pjdesa')->nullable()->index();
            $table->string('email', 200)->index();
            $table->uuid('id_desa')->index();
            $table->date('bulan');
            // Urutan kolom = kolom D–W format Excel "Data Pengelolaan Sampah"
            $table->unsignedInteger('jml_rw');
            $table->unsignedInteger('jml_penduduk');
            $table->unsignedInteger('jml_rumah');
            $table->unsignedInteger('jml_rumah_memilah');
            $table->float('persen_ketaatan')->nullable();
            $table->decimal('timbulan', 12, 2);
            $table->decimal('organik_sumber', 12, 2);
            $table->string('organik_metode')->nullable();
            $table->unsignedInteger('organik_metode_unit')->default(0);
            $table->decimal('organik_dlh', 12, 2);
            $table->string('organik_dlh_fasilitas')->nullable();
            $table->string('organik_dlh_lokasi')->nullable();
            $table->decimal('anorganik_sumber', 12, 2);
            $table->string('anorganik_metode')->nullable();
            $table->string('anorganik_metode_lokasi')->nullable();
            $table->unsignedInteger('anorganik_metode_unit')->default(0);
            $table->decimal('pengurangan', 12, 2);
            $table->decimal('belum_terkelola', 12, 2);
            $table->float('persen_pengurangan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Satu data sampah per ketua (email) per bulan
            $table->unique(['email', 'bulan']);
            $table->index('bulan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_sampah');
    }
};
