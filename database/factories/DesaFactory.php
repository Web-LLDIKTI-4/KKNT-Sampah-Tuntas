<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Desa>
 */
class DesaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_kecamatan' => Kecamatan::factory(),
            'desa' => 'Desa '.fake()->unique()->firstName(),
        ];
    }
}
