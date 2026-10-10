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
            'email' => fake()->unique()->safeEmail(),
            'tanggal' => fake()->dateTimeBetween('-60 days')->format('Y-m-d'),
            'deskripsi' => fake()->paragraph(),
            'volume' => fake()->randomFloat(2, 0, 100),
            'satuan' => 'kegiatan',
            'id_kategori' => null,
            'tautan' => null,
        ];
    }
}
