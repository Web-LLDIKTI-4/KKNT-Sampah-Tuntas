<?php

namespace Database\Factories;

use App\Models\Evaluasikegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluasikegiatan>
 */
class EvaluasikegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pertanyaan' => fake()->sentence().'?',
            'tahun' => (int) date('Y'),
        ];
    }
}
