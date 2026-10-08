<?php

namespace Database\Seeders;

use App\Models\Kpi;
use App\Models\Kpisampah;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Master KPI: Pengurangan Sampah Rumah Tangga (target Kpisampah::TARGET_PENGURANGAN %).
 * Jalankan: php artisan db:seed --class=KpiMasterSeeder
 * Prasyarat: -
 */
class KpiMasterSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        Kpi::firstOrCreate(
            ['nama_kpi' => self::KPI],
            ['target' => Kpisampah::TARGET_PENGURANGAN, 'satuan' => '%'],
        );
    }
}
