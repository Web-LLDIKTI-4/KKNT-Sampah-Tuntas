<?php

namespace Database\Factories;

use App\Models\Kehadiran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kehadiran>
 */
class KehadiranFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-60 days')->format('Y-m-d'),
            'status_kehadiran' => 'hadir',
            'waktu_masuk' => null,
            'latitude_datang' => -6.8992480,
            'longitude_datang' => 107.6377199,
            'waktu_pulang' => null,
            'latitude_pulang' => -6.8992480,
            'longitude_pulang' => 107.6377199,
            'keterangan' => null,
        ];
    }
}
