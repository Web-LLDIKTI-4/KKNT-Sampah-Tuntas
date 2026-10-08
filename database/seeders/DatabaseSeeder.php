<?php

namespace Database\Seeders;

use App\Models\Kpisampah;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan wajib: tiap seeder mengambil prasyarat dari hasil seeder sebelumnya
        $this->call([
            // WilayahSeeder::class,
            // KpiMasterSeeder::class,
            AkunPimpinanSeeder::class,
            // PerguruanTinggiSeeder::class,
            // DplSeeder::class,
            // MahasiswaSeeder::class,
            // KehadiranSeeder::class,
            // LogHarianSeeder::class,
            // LogBulananSeeder::class,
            // KpiCapaianSeeder::class,
            // PendataanPemilahanSeeder::class,
            // RencanaKerjaSeeder::class,
            // PenilaianSeeder::class,
            // EvaluasiSaranSeeder::class,
        ]);

        // Wilayah/PT ikut berganti UUID; cache publik lama tidak boleh dipakai
        Kpisampah::forgetPublicCache();
    }
}
