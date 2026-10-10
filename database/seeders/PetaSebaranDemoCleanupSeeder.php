<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Hapus data demo peta sebaran.
 * Jalankan: php artisan db:seed --class=PetaSebaranDemoCleanupSeeder
 */
class PetaSebaranDemoCleanupSeeder extends Seeder
{
    public function run(): void
    {
        PetaSebaranDemoSeeder::guard();

        DB::transaction(fn () => PetaSebaranDemoSeeder::cleanup(fn (string $m) => $this->command?->warn($m)));
        PetaSebaranDemoSeeder::flushCaches();
    }
}
