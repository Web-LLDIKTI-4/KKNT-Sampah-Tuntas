<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pic_dlh', function (Blueprint $table) {
            $table->uuid('id_pic_dlh')->primary();
            // Restrict: kecamatan tidak boleh terhapus selama masih punya Kontak Pelaksana Program
            $table->foreignUuid('id_kecamatan')
                ->index()
                ->constrained('kecamatan', 'id_kecamatan')
                ->restrictOnDelete();
            $table->string('nama', 200);
            $table->string('kontak', 50);
            $table->string('keterangan', 20)->comment('ketua|anggota|lldikti, lihat Picdlh::KETERANGAN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pic_dlh');
    }
};
