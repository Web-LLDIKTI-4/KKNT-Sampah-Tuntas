<?php

namespace Database\Seeders;

use App\Models\PenguranganSampah;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan wajib: tiap seeder mengambil prasyarat dari hasil seeder sebelumnya
        $this->call([
            WilayahSeeder::class,
            KategoriKegiatanSeeder::class,
            AkunPimpinanSeeder::class,
            PerguruanTinggiSeeder::class,
            DplSeeder::class,
            MahasiswaSeeder::class,
            // KehadiranSeeder::class,
            // LogHarianSeeder::class,
            // LogBulananSeeder::class,
            // CapaianKegiatanSeeder::class,
            // PendataanPemilahanSeeder::class,
            // RencanaKerjaSeeder::class,
            // PenilaianSeeder::class,
            // EvaluasiSaranSeeder::class,
        ]);

        // Wilayah/PT ikut berganti UUID; cache publik lama tidak boleh dipakai
        PenguranganSampah::forgetPublicCache();
    }
}
