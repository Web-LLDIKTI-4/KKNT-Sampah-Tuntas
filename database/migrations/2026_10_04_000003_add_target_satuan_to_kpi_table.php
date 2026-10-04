<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi', function (Blueprint $table) {
            $table->decimal('target', 10, 2)->nullable()->after('nama_kpi');
            $table->string('satuan', 50)->nullable()->after('target');
        });
    }

    public function down(): void
    {
        Schema::table('kpi', function (Blueprint $table) {
            $table->dropColumn(['target', 'satuan']);
        });
    }
};
