<?php

namespace Database\Factories;

use App\Models\Logbulanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logbulanan>
 */
class LogbulananFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tahun' => (int) date('Y'),
            'bulan' => fake()->numberBetween(1, 12),
            'deskripsi' => fake()->paragraphs(2, true),
            'tautan' => fake()->url(),
            'nilai' => null,
            'status_ajuan' => fake()->randomElement(['draf', 'ajuan', 'acc']),
        ];
    }
}
