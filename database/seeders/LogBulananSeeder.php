<?php

namespace Database\Seeders;

use App\Models\Logbulanan;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Laporan bulanan 2 bulan terakhir (bukan bulan berjalan) tiap mahasiswa kelompok. Kunci: email + tahun + bulan.
 * Jalankan: php artisan db:seed --class=LogBulananSeeder
 * Prasyarat: MahasiswaSeeder
 */
class LogBulananSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $mahasiswa = $this->mahasiswaKelompok();
        $this->seedRandom();

        foreach ($mahasiswa as $mhs) {
            foreach ([2, 1] as $offset) {
                $bulan = Carbon::now()->subMonthsNoOverflow($offset);
                $kunci = ['email' => $mhs->email, 'tahun' => $bulan->year, 'bulan' => $bulan->month];

                Logbulanan::firstOrCreate($kunci, Logbulanan::factory()->raw($kunci));
            }
        }
    }
}
