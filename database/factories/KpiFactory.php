<?php

namespace Database\Factories;

use App\Models\Kpi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kpi>
 */
class KpiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_kpi' => fake()->randomElement(['Pemberdayaan UMKM', 'Digitalisasi Desa', 'Kesehatan Masyarakat', 'Pendidikan Anak', 'Lingkungan Hidup']).' '.fake()->unique()->numberBetween(1, 999),
        ];
    }
}
