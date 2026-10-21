<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelaksanaan_kkn', function (Blueprint $table) {
            $table->uuid('id_pelaksanaan')->primary();
            // Unique: 1 pelaksanaan per PT; index unique ini juga melayani FK
            $table->string('kodept', 10)->unique()->comment('NPSN PT (= users.email akun PT)');
            $table->foreign('kodept')
                ->references('npsn')
                ->on('ref_satuanpendidikan')
                ->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->foreignUuid('updated_by')
                ->nullable()
                ->index()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelaksanaan_kkn');
    }
};
