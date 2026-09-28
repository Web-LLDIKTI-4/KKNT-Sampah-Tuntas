<?php

namespace Database\Factories;

use App\Models\Satuanpendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Satuanpendidikan>
 */
class SatuanpendidikanFactory extends Factory
{
    public function definition(): array
    {
        $nama = 'Universitas '.fake()->unique()->lastName();

        return [
            'nm_lemb' => $nama,
            'npsn' => '04'.fake()->unique()->numerify('####'),
            'nm_singkat' => strtoupper(substr(str_replace('Universitas ', 'U', $nama), 0, 6)),
            'id_bp' => (string) fake()->numberBetween(1, 5),
            'jln' => fake()->streetAddress(),
            'id_wil' => '026000',
            'kode_pos' => fake()->postcode(),
            'no_tel' => fake()->phoneNumber(),
            'no_fax' => null,
            'email' => fake()->unique()->safeEmail(),
            'website' => fake()->url(),
            'stat_sp' => 'A',
            'sk_pendirian_sp' => fake()->bothify('SK/###/????/'.fake()->year()),
            'tgl_sk_pendirian_sp' => fake()->date(),
            'tgl_berdiri' => fake()->date(),
            'id_stat_milik' => 'S',
            'last_update' => fake()->dateTimeBetween('-1 year')->format('M d Y h:i:s:A'),
            'kota_kabupaten' => 'Kota Bandung',
            'provinsi' => 'Jawa Barat',
        ];
    }
}
