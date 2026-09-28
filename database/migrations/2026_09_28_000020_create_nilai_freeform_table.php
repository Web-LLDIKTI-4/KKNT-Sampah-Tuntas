<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_freeform', function (Blueprint $table) {
            $table->uuid('id_freeform')->primary();
            $table->uuid('id_mahasiswa')->nullable()->index();
            $table->string('freeform', 150)->nullable();
            $table->string('nilai_dpl', 10)->nullable();
            $table->string('nilai_dpa', 10)->nullable();
            $table->string('email_dpl', 200)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_freeform');
    }
};
