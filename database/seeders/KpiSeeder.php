<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Seeder;

/**
 * Data dummy KPI: master KPI (bila kosong), ketua kelompok dummy (@kknt.test) untuk PT yang belum punya ketua
 * (1 ketua di 1 kelurahan per PT),
 * dan isian capaian campuran (selesai / proses / belum ditindaklanjuti / belum mengisi).
 * Jalankan: php artisan db:seed --class=KpiSeeder
 */
class KpiSeeder extends Seeder
{
    private const DOMAIN = '@kknt.test';

    private const KPI = ['Pengurangan Sampah Rumah Tangga', 'Bank Sampah', 'Pengolahan Sampah Organik'];

    private const JUMLAH_PT = 3;

    public function run(): void
    {
        $kpis = $this->kpis();
        $this->ketuaDummy();

        // Hanya ketua dummy yang belum punya isian, data ketua asli tidak disentuh
        $ketua = Pjdesa::where('email', 'like', '%'.self::DOMAIN)
            ->whereNotIn('email', Kpicapaian::select('email')->whereNotNull('email'))
            ->get();

        foreach ($ketua as $pj) {
            // UNIQUE(email, bulan): tiap KPI di bulan berbeda, mundur dari bulan ini
            foreach ($kpis->values() as $mundur => $kpi) {
                $this->isiCapaian($pj, $kpi, $mundur);
            }
        }

        $this->command?->info('KPI dummy: '.$kpis->count().' KPI, '.$ketua->count().' ketua kelompok diisi.');
    }

    private function kpis()
    {
        if (Kpi::exists()) {
            return Kpi::all();
        }

        return collect(self::KPI)->map(fn ($nama) => Kpi::create(['nama_kpi' => $nama]));
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

        // 1 PT = 1 kelurahan: PT yang sudah punya ketua tidak ditambah; PT baru mendapat 1 ketua di kelurahan baru
        $kecamatan = null;

        foreach ($pts->values() as $i => $pt) {
            if (Pjdesa::whereIn('email', Mahasiswa::where('kodept', $pt->npsn)->select('email'))->exists()) {
                continue;
            }

            $email = 'ketua.pt'.($i + 1).self::DOMAIN;
            $mhs = Mahasiswa::factory()->create([
                'email' => $email,
                'kodept' => $pt->npsn,
                'location_program' => $lokasi[$i % $lokasi->count()]->id,
            ]);
            $kecamatan ??= Kecamatan::factory()->create();
            $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan]);

            Mahasiswa_lokasi::create([
                'tahun' => (int) date('Y'),
                'id_mahasiswa' => $mhs->id_mahasiswa,
                'id_desa' => $desa->id_desa,
                'user_in_up' => $email,
            ]);
            Pjdesa::create(['email' => $email, 'id_desa' => $desa->id_desa]);
        }
    }

    private function isiCapaian(Pjdesa $pj, Kpi $kpi, int $mundur): void
    {
        $acak = random_int(1, 100);
        if ($acak > 85) {
            return; // belum mengisi
        }

        Kpicapaian::factory()->create([
            'id_kpi' => $kpi->id_kpi,
            'id_pjdesa' => $pj->id_pjdesa,
            'email' => $pj->email,
            'bulan' => now()->startOfMonth()->subMonths($mundur)->toDateString(),
            'status_capaian' => $acak <= 60 ? 'Y' : ($acak <= 75 ? 'P' : 'N'),
        ]);
    }
}
