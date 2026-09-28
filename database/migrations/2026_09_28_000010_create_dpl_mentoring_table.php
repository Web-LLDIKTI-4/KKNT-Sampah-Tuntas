<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpl_mentoring', function (Blueprint $table) {
            $table->uuid('id_mentoring')->primary();
            $table->string('email_mahasiswa', 100)->nullable()->index();
            $table->string('email_dpl', 100)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpl_mentoring');
    }
};
