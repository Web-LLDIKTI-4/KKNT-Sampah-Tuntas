<?php

namespace Database\Factories;

use App\Models\RencanaKerja;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * kodept & uploaded_by tidak fillable, tapi factory tetap bisa mengisinya (unguarded).
 * file_path default menunjuk file yang tidak ada di disk; test wajib menimpanya bila file dibutuhkan.
 *
 * @extends Factory<RencanaKerja>
 */
class RencanaKerjaFactory extends Factory
{
    public function definition(): array
    {
        $judul = 'Rencana Kerja '.rtrim(fake()->sentence(3), '.');

        return [
            'kodept' => fn () => Satuanpendidikan::factory()->create()->npsn,
            'judul' => $judul,
            'tahun' => (int) now()->format('Y'),
            'keterangan' => fake()->optional()->paragraph(),
            'file_path' => 'rencana-kerja/'.Str::uuid().'.pdf',
            'nama_file' => Str::slug($judul).'.pdf',
            'ukuran' => 0,
            'mime' => 'application/pdf',
            'uploaded_by' => null,
        ];
    }

    public function forPt(string $npsn): static
    {
        return $this->state(fn () => ['kodept' => $npsn]);
    }
}
