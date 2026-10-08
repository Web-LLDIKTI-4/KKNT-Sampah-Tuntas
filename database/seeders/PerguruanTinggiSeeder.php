<?php

namespace Database\Seeders;

use App\Models\Satuanpendidikan;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * 12 PT simulasi (NPSN 041001–041012) + akun PT (login NPSN); PT ke-i berpasangan 1:1 dengan kelurahan ke-i di WILAYAH.
 * Jalankan: php artisan db:seed --class=PerguruanTinggiSeeder
 * Prasyarat: WilayahSeeder
 */
class PerguruanTinggiSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $penempatan = $this->penempatan();
        $this->seedRandom();

        foreach (self::PERGURUAN_TINGGI as $i => $nama) {
            $npsn = self::npsn($i);
            $pt = Satuanpendidikan::where('npsn', $npsn)->first()
                ?? Satuanpendidikan::factory()->create(['nm_lemb' => $nama, 'npsn' => $npsn]);

            [$lokasi, $desa] = $penempatan[$i];
            $this->user('pt', $pt->npsn, $pt->nm_lemb, ket: 'Login NPSN – Kel. '.$desa->desa.', '.$lokasi->nama_lokasi);
        }

        $this->tampilkanAkun();
    }
}
