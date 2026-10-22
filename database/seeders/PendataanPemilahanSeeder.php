<?php

namespace Database\Seeders;

use App\Models\LokasiProgram;
use App\Models\PendataanPemilahanSampah;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Pendataan pemilahan sampah 3 bulan terakhir (termasuk bulan berjalan) oleh mahasiswa kelompok: 2 rumah tetap per mahasiswa per bulan.
 * Sumber rekap PenguranganSampahService; profil pengelolaan per PT bergiliran ±35% / ±15% / ±5% terkelola.
 * Penurunan timbulan (1 - ΣN / Σ(koef × jiwa)) per PT sesuai PENURUNAN, naik tiap bulan; 1 PT tanpa jiwa (tampil "-").
 * Kunci: email + alamat_rumah + tanggal (tanggal deterministik); maks 1 pendataan per mahasiswa per hari.
 * Jalankan: php artisan db:seed --class=PendataanPemilahanSeeder
 * Prasyarat: PerguruanTinggiSeeder, MahasiswaSeeder
 */
class PendataanPemilahanSeeder extends Seeder
{
    use SeedsDummyData;

    private const RUMAH_PER_MAHASISWA = 2;

    // [rasio terkelola / total, peluang rumah memilah %] per klaster
    private const PROFIL = [[0.35, 70], [0.15, 45], [0.05, 20]];

    // Target % penurunan timbulan bulan berjalan per PT (urut NPSN); null = data lama tanpa jumlah jiwa
    private const PENURUNAN = [45, 30, -8, 55, 22, 12, 38, null, 18, 60, -15, 27];

    // Selisih % penurunan per bulan mundur (bulan lama lebih rendah)
    private const TREN_BULANAN = 4;

    // kg/jiwa/hari bila lokasi program belum punya koefisien timbulan
    private const KOEFISIEN_DEFAULT = 0.36;

    public function run(): void
    {
        $pts = $this->perguruanTinggi();
        $profilPt = $pts->mapWithKeys(fn ($pt, $i) => [$pt->npsn => self::PROFIL[$i % count(self::PROFIL)]]);
        $penurunanPt = $pts->mapWithKeys(fn ($pt, $i) => [$pt->npsn => self::PENURUNAN[$i % count(self::PENURUNAN)]]);
        $mahasiswa = $this->mahasiswaKelompok();
        $koefLokasi = LokasiProgram::pluck('koefisien_timbulan_sampah', 'id');
        $this->seedRandom();

        foreach ($mahasiswa->values() as $m => $mhs) {
            [$rasio, $peluangMemilah] = $profilPt[$mhs->kodept] ?? self::PROFIL[0];
            $koef = (float) ($koefLokasi[$mhs->location_program] ?? 0) ?: self::KOEFISIEN_DEFAULT;
            $penurunan = $penurunanPt->get($mhs->kodept, 30);

            foreach (range(1, self::RUMAH_PER_MAHASISWA) as $r) {
                // Identitas rumah sama tiap bulan agar dihitung 1 rumah di rekap
                $rumah = [
                    'nama_kepala_keluarga' => fake()->name(),
                    'jumlah_anggota_keluarga' => mt_rand(1, 8),
                    'alamat_rumah' => 'Jl. Simulasi No. '.($m * self::RUMAH_PER_MAHASISWA + $r),
                    'rt' => sprintf('%03d', mt_rand(1, 10)),
                    'rw' => sprintf('%03d', mt_rand(1, 5)),
                ];
                $potensi = $koef * $rumah['jumlah_anggota_keluarga'];
                if ($penurunan === null) {
                    $rumah['jumlah_anggota_keluarga'] = null;
                }

                foreach ([2, 1, 0] as $offset) {
                    $timbulan = $potensi * (100 - ($penurunan ?? 30) + self::TREN_BULANAN * $offset) / 100;
                    $this->catat($mhs->email, $rumah, $this->tanggal($offset, $m + $r), $rasio, $peluangMemilah, $timbulan);
                }
            }
        }
    }

    // Tanggal tetap per rumah; bulan berjalan tidak melewati hari ini
    private function tanggal(int $offset, int $geser): string
    {
        $awal = Carbon::today()->startOfMonth()->subMonthsNoOverflow($offset);
        $batas = $offset === 0 ? max(1, min(20, Carbon::today()->day - 1)) : 20;
        $hari = $geser % $batas;

        return $awal->addDays($hari)->toDateString();
    }

    private function catat(string $email, array $rumah, string $tanggal, float $rasio, int $peluangMemilah, float $timbulan): void
    {
        // Log harian sekali sehari: tanggal yang sudah dipakai rumah lain dilewati
        if (PendataanPemilahanSampah::where('email', $email)->where('tanggal', $tanggal)->where('alamat_rumah', '<>', $rumah['alamat_rumah'])->exists()) {
            return;
        }

        // 1 log = sampah 1 hari: total = target timbulan ±10%
        $total = $timbulan * mt_rand(90, 110) / 100;
        $terkelola = $total * $rasio * mt_rand(90, 110) / 100;
        $organik = round($terkelola * mt_rand(55, 75) / 100, 2);
        $anorganik = round($terkelola - $organik, 2);
        $residu = round($total - $organik - $anorganik, 2);

        $data = PendataanPemilahanSampah::factory()->raw($rumah + [
            'email' => $email,
            'tanggal' => $tanggal,
            'memilah' => mt_rand(1, 100) <= $peluangMemilah,
            'organik_kg' => $organik,
            'anorganik_kg' => $anorganik,
            'residu_kg' => $residu,
        ]);

        PendataanPemilahanSampah::updateOrCreate(
            ['email' => $email, 'alamat_rumah' => $rumah['alamat_rumah'], 'tanggal' => $tanggal],
            $data,
        );
    }
}
