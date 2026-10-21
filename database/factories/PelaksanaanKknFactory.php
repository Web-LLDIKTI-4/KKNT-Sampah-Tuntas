<?php

namespace Database\Factories;

use App\Models\PelaksanaanKkn;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * kodept & updated_by tidak fillable, tapi factory tetap bisa mengisinya (unguarded).
 *
 * @extends Factory<PelaksanaanKkn>
 */
class PelaksanaanKknFactory extends Factory
{
    public function definition(): array
    {
        $mulai = now()->startOfDay()->addDays(fake()->numberBetween(0, 60));

        return [
            'kodept' => fn () => Satuanpendidikan::factory()->create()->npsn,
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $mulai->copy()->addDays(fake()->numberBetween(0, 45))->toDateString(),
            'updated_by' => null,
        ];
    }

    public function forPt(string $npsn): static
    {
        return $this->state(fn () => ['kodept' => $npsn]);
    }
}
