<?php

namespace Database\Factories;

use App\Models\KategoriKegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriKegiatan>
 */
class KategoriKegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_kategori' => fake()->randomElement(['Pemberdayaan UMKM', 'Digitalisasi Desa', 'Kesehatan Masyarakat', 'Pendidikan Anak', 'Lingkungan Hidup']).' '.fake()->unique()->numberBetween(1, 999),
        ];
    }
}
