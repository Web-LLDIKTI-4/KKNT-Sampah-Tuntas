<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Wilayah simulasi: 3 lokasi program, 6 kecamatan, 12 kelurahan.
 * Jalankan: php artisan db:seed --class=WilayahSeeder
 * Prasyarat: -
 */
class WilayahSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        foreach (self::WILAYAH as $namaLokasi => $kecamatanList) {
            LokasiProgram::firstOrCreate(['nama_lokasi' => $namaLokasi]);

            foreach ($kecamatanList as $namaKecamatan => $desaList) {
                $kecamatan = Kecamatan::firstOrCreate(['kecamatan' => $namaKecamatan]);
                foreach ($desaList as $namaDesa) {
                    Desa::firstOrCreate(['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $namaDesa]);
                }
            }
        }
    }
}
