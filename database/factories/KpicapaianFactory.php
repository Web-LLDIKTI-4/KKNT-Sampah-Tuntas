<?php

namespace Database\Factories;

use App\Models\Kpicapaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kpicapaian>
 */
class KpicapaianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tahapan' => 1,
            'realisasi' => fake()->numberBetween(0, 100),
            'status_capaian' => fake()->randomElement(['Y', 'P']),
            'tautan' => fake()->url(),
            'permasalahan' => fake()->sentence(),
            'solusi' => fake()->sentence(),
            'kendala' => fake()->sentence(),
        ];
    }
}
