<?php

namespace Database\Factories;

use App\Models\Saran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Saran>
 */
class SaranFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->safeEmail(),
            'saran' => fake()->paragraph(),
        ];
    }
}
