<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kecamatan>
 */
class KecamatanFactory extends Factory
{
    public function definition(): array
    {
        return ['kecamatan' => 'Kecamatan '.fake()->unique()->lastName()];
    }
}
