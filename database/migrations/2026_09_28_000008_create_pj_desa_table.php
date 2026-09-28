<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pj_desa', function (Blueprint $table) {
            $table->uuid('id_pjdesa')->primary();
            $table->string('email', 200)->nullable()->index();
            $table->uuid('id_desa')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pj_desa');
    }
};
