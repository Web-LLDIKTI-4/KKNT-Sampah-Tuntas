<?php

namespace Database\Factories;

use App\Models\Kpitarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kpitarget>
 */
class KpitargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tahapan' => 'Tahap 1',
            'nama_kpitarget' => fake()->sentence(4),
            'target' => 25,
            'satuan' => '%',
        ];
    }
}
