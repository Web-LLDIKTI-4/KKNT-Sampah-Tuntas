<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Seeder;

/**
 * Data dummy KPI: master KPI (bila kosong), ketua kelompok dummy (@kknt.test) dari beberapa PT,
 * dan isian capaian campuran (selesai / proses / belum ditindaklanjuti / belum mengisi).
 * Jalankan: php artisan db:seed --class=KpiSeeder
 */
class KpiSeeder extends Seeder
{
    private const DOMAIN = '@kknt.test';

    private const KPI = [
        'Pengurangan Sampah Rumah Tangga' => [
            ['Sosialisasi pemilahan sampah', 10, 'kali'],
            ['Rumah tangga memilah sampah', 50, 'KK'],
        ],
        'Bank Sampah' => [
            ['Pembentukan bank sampah', 1, 'unit'],
            ['Nasabah bank sampah aktif', 30, 'orang'],
        ],
        'Pengolahan Sampah Organik' => [
            ['Produksi kompos', 100, 'kg'],
            ['Pelatihan budidaya maggot BSF', 2, 'kali'],
        ],
    ];

    private const JUMLAH_PT = 3;

    private const KETUA_PER_PT = 4;

    public function run(): void
    {
        $targets = $this->targets();
        $this->ketuaDummy();

        // Hanya ketua dummy yang belum punya isian, data ketua asli tidak disentuh
        $ketua = Pjdesa::where('email', 'like', '%'.self::DOMAIN)
            ->whereNotIn('email', Kpicapaian::select('email')->whereNotNull('email'))
            ->get();

        foreach ($ketua as $pj) {
            foreach ($targets as $target) {
                $this->isiCapaian($pj, $target);
            }
        }

        $this->command?->info('KPI dummy: '.$targets->count().' kegiatan, '.$ketua->count().' ketua kelompok diisi.');
    }

    private function targets()
    {
        if (Kpitarget::exists()) {
            return Kpitarget::all();
        }

        foreach (self::KPI as $nama => $kegiatan) {
            $kpi = Kpi::create(['nama_kpi' => $nama]);
            foreach ($kegiatan as [$namaKegiatan, $target, $satuan]) {
                Kpitarget::create(['id_kpi' => $kpi->id_kpi, 'kegiatan' => $namaKegiatan, 'target' => $target, 'satuan' => $satuan]);
            }
        }

        return Kpitarget::all();
    }

    private function ketuaDummy(): void
    {
        $lokasi = LokasiProgram::orderBy('nama_lokasi')->get();
        if ($lokasi->isEmpty()) {
            $lokasi = collect([LokasiProgram::create(['nama_lokasi' => 'Kota Bandung'])]);
        }

        $pts = Satuanpendidikan::orderBy('nm_lemb')->limit(self::JUMLAH_PT)->get();
        while ($pts->count() < self::JUMLAH_PT) {
            $pts->push(Satuanpendidikan::factory()->create());
        }

        $kecamatan = Kecamatan::factory()->count(2)->create();

        foreach ($pts->values() as $i => $pt) {
            foreach (range(1, self::KETUA_PER_PT) as $n) {
                $email = 'ketua'.$n.'.pt'.($i + 1).self::DOMAIN;
                if (Mahasiswa::where('email', $email)->exists()) {
                    continue;
                }

                $mhs = Mahasiswa::factory()->create([
                    'email' => $email,
                    'kodept' => $pt->npsn,
                    'location_program' => $lokasi[($i + $n) % $lokasi->count()]->id,
                ]);
                $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->random()->id_kecamatan]);

                Mahasiswa_lokasi::create([
                    'tahun' => (int) date('Y'),
                    'id_mahasiswa' => $mhs->id_mahasiswa,
                    'id_desa' => $desa->id_desa,
                    'user_in_up' => $email,
                ]);
                Pjdesa::create(['email' => $email, 'id_desa' => $desa->id_desa]);
            }
        }
    }

    private function isiCapaian(Pjdesa $pj, Kpitarget $target): void
    {
        $acak = random_int(1, 100);
        if ($acak > 85) {
            return; // belum mengisi
        }

        // Selesai: 40–130% target (ada yang melebihi target); Proses: sebagian; Belum ditindaklanjuti: 0
        [$status, $faktor] = match (true) {
            $acak <= 60 => ['Y', random_int(40, 130) / 100],
            $acak <= 75 => ['P', random_int(10, 60) / 100],
            default => ['N', 0],
        };

        Kpicapaian::factory()->create([
            'id_kpi' => $target->id_kpi,
            'id_target' => $target->id_target,
            'id_pjdesa' => $pj->id_pjdesa,
            'email' => $pj->email,
            'realisasi' => round((float) $target->target * $faktor),
            'satuan' => $target->satuan,
            'status_capaian' => $status,
        ]);
    }
}
