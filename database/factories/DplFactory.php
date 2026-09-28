<?php

namespace Database\Factories;

use App\Models\Dpl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dpl>
 */
class DplFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nidn' => fake()->unique()->numerify('04########'),
            'nama' => fake()->name().', M.Kom.',
            'email' => fake()->unique()->safeEmail(),
            'prodi' => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi', 'Manajemen', 'Akuntansi']),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
