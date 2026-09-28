<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_satuanpendidikan', function (Blueprint $table) {
            $table->uuid('id_sp')->primary();
            $table->string('nm_lemb')->nullable();
            $table->string('npsn', 10)->unique();
            $table->string('nm_singkat')->nullable();
            $table->string('id_bp')->nullable();
            $table->text('jln')->nullable();
            $table->string('id_wil', 10)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('no_tel', 100)->nullable();
            $table->string('no_fax', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('stat_sp', 100)->nullable();
            $table->string('sk_pendirian_sp', 100)->nullable();
            $table->string('tgl_sk_pendirian_sp', 100)->nullable();
            $table->string('tgl_berdiri', 100)->nullable();
            $table->string('id_stat_milik', 5)->nullable();
            $table->string('last_update', 150)->nullable();
            $table->string('kota_kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_satuanpendidikan');
    }
};
