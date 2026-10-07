<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rencana_kerja', function (Blueprint $table) {
            $table->uuid('id_rencana_kerja')->primary();
            // Cascade hanya jaring pengaman; alur hapus PT wajib hapus via Eloquent agar file ikut terhapus
            $table->string('kodept', 10)->comment('NPSN PT pemilik (= users.email akun PT)');
            $table->foreign('kodept')
                ->references('npsn')
                ->on('ref_satuanpendidikan')
                ->cascadeOnDelete();
            $table->string('judul', 200);
            $table->unsignedSmallInteger('tahun')->index();
            $table->text('keterangan')->nullable()->comment('Plain text, bukan HTML');
            $table->string('file_path', 255)->comment('Path relatif di disk local (storage/app/private), mis. rencana-kerja/xxx.pdf');
            $table->string('nama_file', 255)->comment('Nama asli file, dipakai sebagai nama saat diunduh');
            $table->unsignedBigInteger('ukuran')->comment('Ukuran file dalam byte');
            $table->string('mime', 100)->comment('MIME hasil deteksi server, bukan dari klien');
            $table->foreignUuid('uploaded_by')
                ->nullable()
                ->index()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->timestamps();

            // Prefix kodept juga melayani FK & filter PT
            $table->index(['kodept', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencana_kerja');
    }
};
