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
            'kegiatan' => fake()->unique()->sentence(4),
            'target' => 25,
            'satuan' => '%',
        ];
    }
}
