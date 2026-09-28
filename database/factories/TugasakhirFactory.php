<?php

namespace Database\Factories;

use App\Models\Tugasakhir;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tugasakhir>
 */
class TugasakhirFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tahun' => (int) date('Y'),
            'tautan' => fake()->url(),
            'keterangan' => fake()->sentence(),
            'status_ajuan' => fake()->randomElement(['draf', 'ajuan', 'acc']),
            'nilai_dpl' => null,
        ];
    }
}
