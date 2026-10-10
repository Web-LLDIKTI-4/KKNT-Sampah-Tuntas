<?php

namespace Database\Seeders;

use App\Models\CapaianKegiatan;
use App\Models\Pjdesa;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Tepat 1 capaian kegiatan bulan berjalan per ketua kelompok (pj_desa @kknt.test); status ±60% Y, 15% P, 25% N.
 * Kunci: email + bulan (UNIQUE di DB).
 * Jalankan: php artisan db:seed --class=CapaianKegiatanSeeder
 * Prasyarat: KategoriKegiatanSeeder, MahasiswaSeeder
 */
class CapaianKegiatanSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $kategori = $this->kategoriMaster();
        $ketua = Pjdesa::where('email', 'like', 'ketua.%'.self::DOMAIN)->orderBy('email')->get();
        $this->requireData($ketua->isNotEmpty(), 'MahasiswaSeeder');
        $this->seedRandom();

        // Bulan disimpan awal bulan seperti controller
        $bulan = now()->startOfMonth()->toDateString();

        foreach ($ketua as $pj) {
            $acak = mt_rand(1, 100);
            $data = CapaianKegiatan::factory()->raw([
                'id_kategori' => $kategori->id_kategori,
                'id_pjdesa' => $pj->id_pjdesa,
                'email' => $pj->email,
                'bulan' => $bulan,
                'status_capaian' => $acak <= 60 ? 'Y' : ($acak <= 75 ? 'P' : 'N'),
            ]);

            CapaianKegiatan::firstOrCreate(['email' => $pj->email, 'bulan' => $bulan], $data);
        }
    }
}
