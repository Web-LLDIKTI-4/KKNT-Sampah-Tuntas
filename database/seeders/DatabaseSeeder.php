<?php

namespace Database\Seeders;

use App\Models\Kpisampah;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SimulasiSeeder::class,
            AdminSeeder::class,
        ]);

        // Wilayah/PT ikut berganti UUID; cache publik lama tidak boleh dipakai
        Kpisampah::forgetPublicCache();
    }
}
