<?php

namespace Database\Seeders;

use App\Models\Kehadiran;
use App\Models\Logkegiatan;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Log harian (logkegiatan, deskripsi terisi, KPI master) ±75% dari hari berstatus hadir; tidak pernah di hari blocking.
 * Kunci: email + tanggal (1 log per hari).
 * Jalankan: php artisan db:seed --class=LogHarianSeeder
 * Prasyarat: KpiMasterSeeder, MahasiswaSeeder, KehadiranSeeder
 */
class LogHarianSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $kpi = $this->kpiMaster();
        $mahasiswa = $this->mahasiswaKelompok();
        $hadir = Kehadiran::whereIn('email', $mahasiswa->pluck('email'))
            ->where('status_kehadiran', 'hadir')
            ->where('tanggal', '<', today()->toDateString())
            ->orderBy('email')->orderBy('tanggal')
            ->get(['email', 'tanggal']);
        $this->requireData($hadir->isNotEmpty(), 'KehadiranSeeder');
        $this->seedRandom();

        foreach ($hadir as $row) {
            $tanggal = substr((string) $row->tanggal, 0, 10);
            $data = Logkegiatan::factory()->raw([
                'email' => $row->email,
                'tanggal' => $tanggal,
                'id_kpi' => $kpi->id_kpi,
            ]);

            if (mt_rand(1, 100) <= 75) {
                Logkegiatan::without(['mahasiswa', 'dplmentoring'])->firstOrCreate(['email' => $row->email, 'tanggal' => $tanggal], $data);
            }
        }
    }
}
