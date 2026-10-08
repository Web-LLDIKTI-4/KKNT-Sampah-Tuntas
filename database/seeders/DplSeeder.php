<?php

namespace Database\Seeders;

use App\Models\Dpl;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * 1 DPL + akun per PT simulasi, di lokasi program kelurahan pasangan PT.
 * Jalankan: php artisan db:seed --class=DplSeeder
 * Prasyarat: WilayahSeeder, PerguruanTinggiSeeder
 */
class DplSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $pts = $this->perguruanTinggi();
        $penempatan = $this->penempatan();
        $this->seedRandom();

        foreach ($pts as $i => $pt) {
            [$lokasi] = $penempatan[$i];
            $email = 'dpl.pt'.($i + 1).'.'.self::slugLokasi($lokasi).self::DOMAIN;

            $dpl = Dpl::where('email', $email)->first() ?? Dpl::factory()->create([
                'email' => $email,
                'kodept' => $pt->npsn,
                'location_program' => $lokasi->id,
            ]);
            $this->user('dpl', $dpl->email, $dpl->nama, $lokasi->id, ket: $pt->nm_lemb.' – '.$lokasi->nama_lokasi);
        }

        $this->tampilkanAkun();
    }
}
