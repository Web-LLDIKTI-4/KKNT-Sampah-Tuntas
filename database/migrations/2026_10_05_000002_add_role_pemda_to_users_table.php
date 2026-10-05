<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mahasiswa', 'dpl', 'pt', 'kepala', 'pemda'])->nullable()->default('mahasiswa')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'pemda')->update(['role' => 'kepala']);
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mahasiswa', 'dpl', 'pt', 'kepala'])->nullable()->default('mahasiswa')->change();
        });
    }
};
