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
            $table->unsignedInteger('jml_rw_kbs');
            $table->unsignedInteger('jml_rw_non_kbs');
            $table->unsignedInteger('jml_rumah');
            $table->unsignedInteger('jml_rumah_memilah');
            $table->decimal('persen_ketaatan', 6, 2)->nullable();
            $table->decimal('timbulan', 12, 2);
            $table->decimal('pengurangan_organik', 12, 2);
            $table->decimal('pengurangan_anorganik', 12, 2);
            $table->decimal('pengurangan', 12, 2);
            $table->decimal('residu', 12, 2);
            $table->decimal('persen_pengurangan', 6, 2)->nullable();
            $table->unsignedInteger('jml_bank_sampah');
            $table->timestamps();

            // Satu kelurahan hanya satu data per bulan
            $table->unique(['id_desa', 'bulan']);
            $table->index('bulan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_sampah');
    }
};
