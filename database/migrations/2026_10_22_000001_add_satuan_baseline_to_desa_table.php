<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desa', function (Blueprint $table) {
            $table->decimal('baseline', 10, 2)->nullable()->default(null)->change();
            $table->string('satuan_baseline', 10)->default('%')->after('baseline');
        });
    }

    public function down(): void
    {
        // Null out values that would overflow decimal(5,2) or are not percentages.
        DB::table('desa')
            ->where('satuan_baseline', 'kg')
            ->orWhere('baseline', '>', 999.99)
            ->update(['baseline' => null]);

        Schema::table('desa', function (Blueprint $table) {
            $table->dropColumn('satuan_baseline');
        });

        Schema::table('desa', function (Blueprint $table) {
            $table->decimal('baseline', 5, 2)->nullable()->default(null)->change();
        });
    }
};
