<?php

namespace Database\Factories;

use App\Models\Nilaikonversi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nilaikonversi>
 */
class NilaikonversiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matakuliah' => fake()->randomElement(['KKN Tematik', 'Pengabdian Masyarakat', 'Kewirausahaan', 'Metodologi Penelitian']),
            'sks' => fake()->numberBetween(2, 4),
            'nilai_dpl' => (string) fake()->numberBetween(65, 100),
            'nilai_dpa' => (string) fake()->numberBetween(65, 100),
        ];
    }
}
