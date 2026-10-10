<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi', function (Blueprint $table) {
            foreach (['satuan', 'target'] as $column) {
                if (Schema::hasColumn('kpi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi', function (Blueprint $table) {
            if (! Schema::hasColumn('kpi', 'target')) {
                $table->decimal('target', 10, 2)->nullable()->after('nama_kpi');
            }
            if (! Schema::hasColumn('kpi', 'satuan')) {
                $table->string('satuan', 50)->nullable()->after('target');
            }
        });
    }
};
