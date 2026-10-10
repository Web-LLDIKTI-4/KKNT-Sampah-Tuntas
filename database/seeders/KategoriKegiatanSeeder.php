<?php

namespace Database\Seeders;

use App\Models\KategoriKegiatan;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Master kategori kegiatan: Pengurangan Sampah Rumah Tangga.
 * Jalankan: php artisan db:seed --class=KategoriKegiatanSeeder
 * Prasyarat: -
 */
class KategoriKegiatanSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        KategoriKegiatan::firstOrCreate(['nama_kategori' => self::KATEGORI]);
    }
}
