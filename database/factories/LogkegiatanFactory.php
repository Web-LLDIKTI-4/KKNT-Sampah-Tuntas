<?php

namespace Database\Factories;

use App\Models\Logkegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logkegiatan>
 */
class LogkegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-60 days')->format('Y-m-d'),
            'deskripsi' => fake()->paragraph(),
            'volume' => (string) fake()->numberBetween(1, 20),
            'satuan' => fake()->randomElement(['kegiatan', 'orang', 'dokumen', 'jam']),
            'tautan' => fake()->url(),
        ];
    }
}
