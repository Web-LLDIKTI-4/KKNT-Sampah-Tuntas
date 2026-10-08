<?php

namespace Database\Seeders;

use App\Models\PendataanPemilahanSampah;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Pendataan pemilahan sampah 3 bulan terakhir (termasuk bulan berjalan) oleh mahasiswa kelompok: 2 rumah tetap per mahasiswa per bulan.
 * Sumber rekap KpiSampahService; profil per PT bergiliran hijau (±35%), kuning (±15%), merah (±5%) pengurangan.
 * Kunci: email + alamat_rumah + tanggal (tanggal deterministik).
 * Jalankan: php artisan db:seed --class=PendataanPemilahanSeeder
 * Prasyarat: PerguruanTinggiSeeder, MahasiswaSeeder
 */
class PendataanPemilahanSeeder extends Seeder
{
    use SeedsDummyData;

    private const RUMAH_PER_MAHASISWA = 2;

    // [rasio terkelola / total, peluang rumah memilah %] per klaster
    private const PROFIL = [[0.35, 70], [0.15, 45], [0.05, 20]];

    public function run(): void
    {
        $profilPt = $this->perguruanTinggi()->mapWithKeys(fn ($pt, $i) => [$pt->npsn => self::PROFIL[$i % count(self::PROFIL)]]);
        $mahasiswa = $this->mahasiswaKelompok();
        $this->seedRandom();

        foreach ($mahasiswa->values() as $m => $mhs) {
            [$rasio, $peluangMemilah] = $profilPt[$mhs->kodept] ?? self::PROFIL[0];

            foreach (range(1, self::RUMAH_PER_MAHASISWA) as $r) {
                // Identitas rumah sama tiap bulan agar dihitung 1 rumah di rekap
                $rumah = [
                    'nama_kepala_keluarga' => fake()->name(),
                    'alamat_rumah' => 'Jl. Simulasi No. '.($m * self::RUMAH_PER_MAHASISWA + $r),
                    'rt' => sprintf('%03d', mt_rand(1, 10)),
                    'rw' => sprintf('%03d', mt_rand(1, 5)),
                ];

                foreach ([2, 1, 0] as $offset) {
                    $this->catat($mhs->email, $rumah, $this->tanggal($offset, $m + $r), $rasio, $peluangMemilah);
                }
            }
        }
    }

    // Tanggal tetap per rumah; bulan berjalan tidak melewati hari ini
    private function tanggal(int $offset, int $geser): string
    {
        $awal = Carbon::today()->startOfMonth()->subMonthsNoOverflow($offset);
        $hari = $geser % 20;
        if ($offset === 0) {
            $hari = min($hari, Carbon::today()->day - 1);
        }

        return $awal->addDays($hari)->toDateString();
    }

    private function catat(string $email, array $rumah, string $tanggal, float $rasio, int $peluangMemilah): void
    {
        $organik = mt_rand(50, 200) / 100;
        $anorganik = mt_rand(20, 100) / 100;
        $residu = round(($organik + $anorganik) * (1 - $rasio) / $rasio * mt_rand(90, 110) / 100, 2);

        $data = PendataanPemilahanSampah::factory()->raw($rumah + [
            'email' => $email,
            'tanggal' => $tanggal,
            'memilah' => mt_rand(1, 100) <= $peluangMemilah,
            'organik_kg' => $organik,
            'anorganik_kg' => $anorganik,
            'residu_kg' => $residu,
        ]);

        PendataanPemilahanSampah::firstOrCreate(
            ['email' => $email, 'alamat_rumah' => $rumah['alamat_rumah'], 'tanggal' => $tanggal],
            $data,
        );
    }
}
