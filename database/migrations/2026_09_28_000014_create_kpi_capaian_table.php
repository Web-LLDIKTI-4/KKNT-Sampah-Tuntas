<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_capaian', function (Blueprint $table) {
            $table->uuid('id_capaian')->primary();
            $table->uuid('id_kpi')->nullable()->index();
            $table->integer('tahapan')->nullable();
            $table->uuid('id_target')->nullable()->index();
            $table->uuid('id_pjdesa')->nullable()->index();
            $table->string('email', 200)->nullable()->index();
            $table->string('status_capaian', 5)->nullable();
            $table->text('tautan')->nullable();
            $table->text('permasalahan')->nullable();
            $table->text('solusi')->nullable();
            $table->text('kendala')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_capaian');
    }
};
