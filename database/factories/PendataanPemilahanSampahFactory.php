<?php

namespace Database\Factories;

use App\Models\PendataanPemilahanSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendataanPemilahanSampah>
 */
class PendataanPemilahanSampahFactory extends Factory
{
    protected $model = PendataanPemilahanSampah::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'tanggal' => fake()->dateTimeBetween('-60 days')->format('Y-m-d'),
            'nama_kepala_keluarga' => fake()->name(),
            'alamat_rumah' => fake()->streetAddress(),
            'rt' => sprintf('%03d', fake()->numberBetween(1, 15)),
            'rw' => sprintf('%03d', fake()->numberBetween(1, 10)),
            'memilah' => fake()->boolean(),
            'organik_kg' => fake()->randomFloat(2, 0, 5),
            'anorganik_kg' => fake()->randomFloat(2, 0, 3),
            'residu_kg' => fake()->randomFloat(2, 0, 2),
        ];
    }
}
