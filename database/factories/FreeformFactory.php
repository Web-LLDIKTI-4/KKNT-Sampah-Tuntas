<?php

namespace Database\Factories;

use App\Models\Freeform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Freeform>
 */
class FreeformFactory extends Factory
{
    public function definition(): array
    {
        return [
            'freeform' => fake()->randomElement(['Kepemimpinan', 'Kerja Sama Tim', 'Komunikasi', 'Inisiatif']),
            'nilai_dpl' => (string) fake()->numberBetween(70, 100),
            'nilai_dpa' => (string) fake()->numberBetween(70, 100),
        ];
    }
}
