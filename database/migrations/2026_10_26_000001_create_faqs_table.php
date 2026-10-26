<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Urutan tampil = id ASC; cukup PK, tanpa index tambahan
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan', 255);
            $table->text('jawaban')->comment('Plain text, bukan HTML');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
