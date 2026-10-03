<?php

namespace Database\Factories;

use App\Models\Panduan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * file_path & ukuran default menunjuk file yang tidak ada di disk;
 * seeder/test wajib menimpanya dengan file nyata bila file dibutuhkan.
 *
 * @extends Factory<Panduan>
 */
class PanduanFactory extends Factory
{
    public function definition(): array
    {
        $judul = 'Panduan '.rtrim(fake()->unique()->sentence(3), '.');

        return [
            'judul' => $judul,
            'deskripsi' => fake()->optional()->paragraph(),
            'file_path' => 'panduan/'.Str::uuid().'.pdf',
            'nama_file' => Str::slug($judul).'.pdf',
            'ukuran' => 0,
            'mime' => 'application/pdf',
            'peruntukan' => 'semua', // tidak dipakai UI sejak iterasi 2
            'is_aktif' => fake()->boolean(80),
            'uploaded_by' => null,
        ];
    }
}
