<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Persentase disimpan sebagai float (nilai tetap dibulatkan 2 desimal oleh Kpisampah::persen())
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_sampah', function (Blueprint $table) {
            $table->float('persen_ketaatan')->nullable()->change();
            $table->float('persen_pengurangan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kpi_sampah', function (Blueprint $table) {
            $table->decimal('persen_ketaatan', 6, 2)->nullable()->change();
            $table->decimal('persen_pengurangan', 6, 2)->nullable()->change();
        });
    }
};
