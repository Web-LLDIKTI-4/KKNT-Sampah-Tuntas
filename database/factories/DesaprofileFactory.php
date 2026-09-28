<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\Desaprofile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Desaprofile>
 */
class DesaprofileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_desa' => Desa::factory(),
            'tahun' => (int) date('Y'),
            'potensi' => fake()->paragraph(),
            'masalah' => fake()->paragraph(),
        ];
    }
}
