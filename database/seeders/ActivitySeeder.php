<?php

namespace Database\Seeders;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Freeform;
use App\Models\Kehadiran;
use App\Models\Kpicapaian;
use App\Models\Kpi;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Pjdesa;
use App\Models\Saran;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $kpiIds = Kpi::pluck('id_kpi');
        $mentoring = Dplmentoring::pluck('email_dpl', 'email_mahasiswa');

        foreach (Mahasiswa::all() as $mhs) {
            $emailDpl = $mentoring[$mhs->email] ?? null;

            Logkegiatan::factory()->count(8)->create(['email' => $mhs->email]);

            foreach ([1, 2] as $offset) {
                $bulan = Carbon::now()->subMonthsNoOverflow($offset);
                Logbulanan::factory()->create([
                    'email' => $mhs->email,
                    'tahun' => $bulan->year,
                    'bulan' => $bulan->month,
                ]);
            }

            foreach (range(1, 7) as $day) {
                $tanggal = Carbon::today()->subDays($day);
                Kehadiran::factory()->create([
                    'email' => $mhs->email,
                    'tanggal' => $tanggal->toDateString(),
                    'waktu_masuk' => $tanggal->copy()->setTime(8, random_int(0, 30)),
                    'waktu_pulang' => $tanggal->copy()->setTime(16, random_int(0, 30)),
                ]);
            }

            Tugasakhir::factory()->create(['email' => $mhs->email, 'email_dpl' => $emailDpl]);
            Nilaikonversi::factory()->count(2)->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => $emailDpl]);
            Freeform::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => $emailDpl]);
        }

        // Capaian KPI diisi oleh ketua kelompok (pj desa)
        foreach (Pjdesa::all() as $pj) {
            // UNIQUE(email, bulan): tiap KPI di bulan berbeda, mundur dari bulan ini
            foreach ($kpiIds->values() as $mundur => $idKpi) {
                Kpicapaian::factory()->create([
                    'id_kpi' => $idKpi,
                    'id_pjdesa' => $pj->id_pjdesa,
                    'email' => $pj->email,
                    'bulan' => now()->startOfMonth()->subMonths($mundur)->toDateString(),
                ]);
            }
        }

        foreach (Dpl::all() as $dpl) {
            Dpllaporan::factory()->count(2)
                ->sequence(['bulan' => now()->subMonthNoOverflow()->month], ['bulan' => now()->month])
                ->create(['email' => $dpl->email]);
        }

        $pertanyaan = Evaluasikegiatan::all();
        foreach (Satuanpendidikan::all() as $pt) {
            foreach ($pertanyaan as $evaluasi) {
                Evaluasikegiatanjawaban::create([
                    'id_evaluasi' => $evaluasi->id_evaluasi,
                    'jawaban' => fake()->sentence(),
                    'tahun' => $evaluasi->tahun,
                    'kodept' => $pt->npsn,
                    'user' => $pt->npsn,
                ]);
            }
        }

        Saran::factory()->count(5)->create();
    }
}
