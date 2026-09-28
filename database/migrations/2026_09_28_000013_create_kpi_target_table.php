<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_target', function (Blueprint $table) {
            $table->uuid('id_target')->primary();
            $table->uuid('id_kpi')->nullable()->index();
            $table->string('tahapan', 50)->nullable();
            $table->string('nama_kpitarget', 200)->nullable();
            $table->string('persen', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_target');
    }
};
