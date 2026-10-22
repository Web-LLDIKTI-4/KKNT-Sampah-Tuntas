<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Wilayah simulasi: 3 lokasi program (koefisien timbulan diisi bila kosong), 6 kecamatan, 12 kelurahan berkoordinat.
 * Jalankan: php artisan db:seed --class=WilayahSeeder
 * Prasyarat: -
 */
class WilayahSeeder extends Seeder
{
    use SeedsDummyData;

    // kg/jiwa/hari per lokasi program
    private const KOEFISIEN = ['Kota Bandung' => 0.7, 'Kabupaten Bandung' => 0.55, 'Kota Cimahi' => 0.6];

    // Perkiraan titik pusat kelurahan [lat, lng] untuk peta sebaran
    private const KOORDINAT = [
        'Dago' => [-6.8853, 107.6145],
        'Lebakgede' => [-6.8905, 107.6200],
        'Pasteur' => [-6.8935, 107.5935],
        'Cipedes' => [-6.8850, 107.5850],
        'Citeureup' => [-6.9780, 107.6380],
        'Cangkuang Wetan' => [-6.9840, 107.6200],
        'Andir' => [-6.9951, 107.6312],
        'Manggahang' => [-7.0130, 107.6370],
        'Baros' => [-6.8935, 107.5440],
        'Setiamanah' => [-6.8775, 107.5410],
        'Cibabat' => [-6.8705, 107.5480],
        'Pasirkaliki' => [-6.8610, 107.5440],
    ];

    public function run(): void
    {
        foreach (self::WILAYAH as $namaLokasi => $kecamatanList) {
            $lokasi = LokasiProgram::firstOrCreate(['nama_lokasi' => $namaLokasi]);
            // Koefisien yang sudah diisi admin tidak ditimpa
            if ($lokasi->koefisien_timbulan_sampah === null) {
                $lokasi->update(['koefisien_timbulan_sampah' => self::KOEFISIEN[$namaLokasi]]);
            }

            foreach ($kecamatanList as $namaKecamatan => $desaList) {
                $kecamatan = Kecamatan::firstOrCreate(['kecamatan' => $namaKecamatan]);
                foreach ($desaList as $namaDesa) {
                    [$lat, $lng] = self::KOORDINAT[$namaDesa];
                    $desa = Desa::firstOrCreate(
                        ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $namaDesa],
                        ['latitude' => $lat, 'longitude' => $lng],
                    );
                    // Koordinat yang sudah diisi admin tidak ditimpa
                    if ($desa->latitude === null || $desa->longitude === null) {
                        $desa->update(['latitude' => $lat, 'longitude' => $lng]);
                    }
                }
            }
        }
    }
}
