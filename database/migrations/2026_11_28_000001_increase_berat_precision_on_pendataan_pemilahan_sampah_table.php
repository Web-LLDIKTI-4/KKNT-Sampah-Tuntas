<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Berat sampah boleh 3 angka desimal (mis. 0,925 kg); pelebaran skala tidak mengubah data lama
return new class extends Migration
{
    private const KOLOM = ['organik_kg', 'anorganik_kg', 'residu_kg'];

    public function up(): void
    {
        $this->ubahSkala(3);
    }

    public function down(): void
    {
        // Rollback membulatkan nilai 3 desimal ke 2 desimal
        $this->ubahSkala(2);
    }

    private function ubahSkala(int $skala): void
    {
        Schema::table('pendataan_pemilahan_sampah', function (Blueprint $table) use ($skala) {
            foreach (self::KOLOM as $kolom) {
                $table->decimal($kolom, 10, $skala)->change();
            }
        });
    }
};
