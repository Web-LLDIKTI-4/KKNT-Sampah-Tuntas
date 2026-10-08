<?php

namespace Database\Seeders;

use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Saran;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * 3 pertanyaan evaluasi kegiatan tahun berjalan + jawaban tiap PT simulasi; 5 saran (saran{n}@kknt.test).
 * Kunci: pertanyaan + tahun, id_evaluasi + kodept, email saran.
 * Jalankan: php artisan db:seed --class=EvaluasiSaranSeeder
 * Prasyarat: PerguruanTinggiSeeder
 */
class EvaluasiSaranSeeder extends Seeder
{
    use SeedsDummyData;

    private const PERTANYAAN = [
        'Apakah program KKNT membantu pengurangan sampah di kelurahan?',
        'Bagaimana koordinasi mahasiswa dengan perangkat kelurahan?',
        'Apa kendala utama pelaksanaan pemilahan sampah rumah tangga?',
    ];

    private const JUMLAH_SARAN = 5;

    public function run(): void
    {
        $pts = $this->perguruanTinggi();
        $this->seedRandom();
        $tahun = (int) date('Y');

        $pertanyaan = collect(self::PERTANYAAN)
            ->map(fn ($teks) => Evaluasikegiatan::firstOrCreate(['pertanyaan' => $teks, 'tahun' => $tahun]));

        foreach ($pts as $pt) {
            foreach ($pertanyaan as $evaluasi) {
                Evaluasikegiatanjawaban::firstOrCreate(
                    ['id_evaluasi' => $evaluasi->id_evaluasi, 'kodept' => $pt->npsn],
                    ['jawaban' => fake()->sentence(), 'tahun' => $evaluasi->tahun, 'user' => $pt->npsn],
                );
            }
        }

        foreach (range(1, self::JUMLAH_SARAN) as $n) {
            $email = 'saran'.$n.self::DOMAIN;
            Saran::firstOrCreate(['email' => $email], Saran::factory()->raw(['email' => $email]));
        }
    }
}
