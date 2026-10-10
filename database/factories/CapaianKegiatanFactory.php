<?php

namespace Database\Factories;

use App\Models\CapaianKegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapaianKegiatan>
 */
class CapaianKegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bulan' => now()->startOfMonth()->toDateString(),
            'status_capaian' => fake()->randomElement(['Y', 'P']),
            'tautan' => fake()->url(),
            'permasalahan' => fake()->sentence(),
            'solusi' => fake()->sentence(),
            'kendala' => fake()->sentence(),
        ];
    }
}
