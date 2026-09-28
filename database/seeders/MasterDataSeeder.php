<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Desaprofile;
use App\Models\Evaluasikegiatan;
use App\Models\Kecamatan;
use App\Models\Kpi;
use App\Models\Kpitarget;
use App\Models\LokasiProgram;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Kota Bandung', 'Kabupaten Bandung', 'Kota Cimahi'] as $nama) {
            LokasiProgram::create(['nama_lokasi' => $nama]);
        }

        Satuanpendidikan::factory()->count(8)->create();

        Kecamatan::factory()->count(4)->create()->each(function (Kecamatan $kecamatan) {
            Desa::factory()->count(3)->create(['id_kecamatan' => $kecamatan->id_kecamatan])
                ->each(fn (Desa $desa) => Desaprofile::factory()->create(['id_desa' => $desa->id_desa]));
        });

        Kpi::factory()->count(4)->create()->each(function (Kpi $kpi) {
            foreach ([1 => '25', 2 => '50', 3 => '75', 4 => '100'] as $tahap => $persen) {
                Kpitarget::factory()->create([
                    'id_kpi' => $kpi->id_kpi,
                    'tahapan' => 'Tahap '.$tahap,
                    'target' => $persen,
                    'satuan' => '%',
                ]);
            }
        });

        Evaluasikegiatan::factory()->count(5)->create();
    }
}
