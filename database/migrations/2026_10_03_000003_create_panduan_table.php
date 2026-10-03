<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panduan', function (Blueprint $table) {
            $table->uuid('id_panduan')->primary();
            $table->string('judul', 200)->index();
            $table->text('deskripsi')->nullable()->comment('Plain text, bukan HTML');
            $table->string('file_path', 255)->comment('Path relatif di disk local (storage/app/private), mis. panduan/xxx.pdf');
            $table->string('nama_file', 255)->comment('Nama asli file, dipakai sebagai nama saat diunduh');
            $table->unsignedBigInteger('ukuran')->comment('Ukuran file dalam byte');
            $table->string('mime', 100)->comment('MIME hasil deteksi server, bukan dari klien');
            $table->enum('peruntukan', ['semua', 'mahasiswa', 'dpl', 'pt', 'kepala'])
                ->default('semua')
                ->index()
                ->comment('Target pembaca; fase ini belum dipakai untuk filter akses (admin-only)');
            $table->boolean('is_aktif')->default(true)->index();
            $table->foreignUuid('uploaded_by')
                ->nullable()
                ->index()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panduan');
    }
};
