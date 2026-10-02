<?php

namespace Database\Seeders;

use App\Models\Kpisampah;
use App\Models\Pjdesa;
use App\Services\KpiSampahService;
use Illuminate\Database\Seeder;

/**
 * Data sampah bulanan dummy: setiap kelurahan ketua kelompok diisi 3 bulan terakhir,
 * ketua ke-3 belum mengisi bulan terakhir. Aman dijalankan ulang (updateOrCreate per kelurahan & bulan).
 * Jalankan: php artisan db:seed --class=KpiSampahSeeder
 */
class KpiSampahSeeder extends Seeder
{
    public function run(KpiSampahService $sampah): void
    {
        mt_srand(2026);

        foreach (Pjdesa::orderBy('email')->get() as $i => $pj) {
            $idDesa = $sampah->desaKetua($pj->email);
            if (! $idDesa) {
                continue;
            }

            $rumah = mt_rand(200, 600);
            foreach ([3, 2, 1] as $mundur) {
                if ($i === 2 && $mundur === 1) {
                    continue;
                }

                $progres = (4 - $mundur) * 5;
                $timbulan = $rumah * mt_rand(12, 18);
                // Rentang lebar agar klaster hijau (<= 20%), kuning (<= 30%), dan merah (> 30%) semuanya muncul
                $organik = round($timbulan * mt_rand(3 + $progres, 25 + $progres) / 100, 2);
                $anorganik = round($timbulan * mt_rand(2, 8) / 100, 2);

                Kpisampah::updateOrCreate(
                    ['id_desa' => $idDesa, 'bulan' => now()->startOfMonth()->subMonths($mundur)->toDateString()],
                    KpiSampahService::hitung([
                        'id_pjdesa' => $pj->id_pjdesa,
                        'email' => $pj->email,
                        'jml_rw_kbs' => mt_rand(1, 5),
                        'jml_rw_non_kbs' => mt_rand(2, 8),
                        'jml_rumah' => $rumah,
                        'jml_rumah_memilah' => (int) round($rumah * mt_rand(20 + $progres, 60 + $progres) / 100),
                        'timbulan' => $timbulan,
                        'pengurangan_organik' => $organik,
                        'pengurangan_anorganik' => $anorganik,
                        'residu' => round($timbulan - $organik - $anorganik, 2),
                        'jml_bank_sampah' => mt_rand(0, 3),
                    ])
                );
            }
        }

        $this->command?->info('Data sampah bulanan: '.Kpisampah::count().' baris.');
    }
}
