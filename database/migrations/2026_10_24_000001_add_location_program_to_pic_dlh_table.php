<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pic_dlh', function (Blueprint $table) {
            $table->foreignUuid('location_program')
                ->nullable()
                ->after('id_kecamatan')
                ->constrained('lokasi_program', 'id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pic_dlh', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_program');
        });
    }
};
