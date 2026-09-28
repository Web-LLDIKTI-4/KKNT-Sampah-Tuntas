<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nim' => fake()->unique()->numerify('10#######'),
            'tahun_masuk' => fake()->numberBetween((int) date('Y') - 4, (int) date('Y') - 2),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'prodi' => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi', 'Manajemen', 'Akuntansi', 'Ilmu Komunikasi']),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
