<?php

namespace Database\Factories;

use App\Models\LokasiProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LokasiProgram>
 */
class LokasiProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_lokasi' => 'Kota '.fake()->unique()->city(),
            'gambar' => null,
        ];
    }
}
