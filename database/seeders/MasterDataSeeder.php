<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    // Hanya data master minimal yang dibutuhkan akun dummy
    public function run(): void
    {
        foreach (['Kota Bandung', 'Kabupaten Bandung', 'Kota Cimahi'] as $nama) {
            LokasiProgram::create(['nama_lokasi' => $nama]);
        }

        Satuanpendidikan::factory()->create();

        $kecamatan = Kecamatan::factory()->create();
        Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan]);
    }
}
