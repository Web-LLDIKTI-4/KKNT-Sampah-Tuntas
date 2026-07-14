<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kehadiran', function (Blueprint $table) {
            $table->decimal('latitude_datang', 10, 7)->nullable()->after('waktu_masuk');
            $table->decimal('longitude_datang', 10, 7)->nullable()->after('latitude_datang');
            $table->decimal('latitude_pulang', 10, 7)->nullable()->after('waktu_pulang');
            $table->decimal('longitude_pulang', 10, 7)->nullable()->after('latitude_pulang');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kehadiran', function (Blueprint $table) {
            $table->dropColumn(['latitude_datang', 'longitude_datang', 'latitude_pulang', 'longitude_pulang']);
        });
    }
};
