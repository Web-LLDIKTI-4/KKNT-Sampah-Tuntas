<?php

namespace Database\Seeders;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Nilaikonversi;
use App\Models\Tugasakhir;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Penilaian mahasiswa kelompok: tugas akhir, 2 nilai konversi, 1 nilai freeform (oleh DPL pembimbing);
 * laporan bulanan DPL bulan lalu & bulan berjalan.
 * Jalankan: php artisan db:seed --class=PenilaianSeeder
 * Prasyarat: DplSeeder, MahasiswaSeeder
 */
class PenilaianSeeder extends Seeder
{
    use SeedsDummyData;

    private const MATAKULIAH = ['KKN Tematik', 'Pengabdian Masyarakat'];

    public function run(): void
    {
        $mahasiswa = $this->mahasiswaKelompok();
        $dpls = Dpl::where('email', 'like', 'dpl.%'.self::DOMAIN)->orderBy('email')->get();
        $this->requireData($dpls->isNotEmpty(), 'DplSeeder');
        $mentoring = Dplmentoring::whereIn('email_mahasiswa', $mahasiswa->pluck('email'))->pluck('email_dpl', 'email_mahasiswa');
        $this->seedRandom();

        foreach ($mahasiswa as $mhs) {
            $emailDpl = $mentoring[$mhs->email];

            $tugas = ['email' => $mhs->email, 'tahun' => (int) date('Y')];
            Tugasakhir::firstOrCreate($tugas, Tugasakhir::factory()->raw($tugas + ['email_dpl' => $emailDpl]));

            foreach (self::MATAKULIAH as $matakuliah) {
                $kunci = ['id_mahasiswa' => $mhs->id_mahasiswa, 'matakuliah' => $matakuliah];
                Nilaikonversi::firstOrCreate($kunci, Nilaikonversi::factory()->raw($kunci + ['email_dpl' => $emailDpl]));
            }

            $kunci = ['id_mahasiswa' => $mhs->id_mahasiswa];
            Freeform::firstOrCreate($kunci, Freeform::factory()->raw($kunci + ['email_dpl' => $emailDpl]));
        }

        foreach ($dpls as $dpl) {
            foreach ([1, 0] as $offset) {
                $bulan = Carbon::now()->subMonthsNoOverflow($offset);
                $kunci = ['email' => $dpl->email, 'tahun' => $bulan->year, 'bulan' => $bulan->month];
                Dpllaporan::firstOrCreate($kunci, Dpllaporan::factory()->raw($kunci));
            }
        }
    }
}
