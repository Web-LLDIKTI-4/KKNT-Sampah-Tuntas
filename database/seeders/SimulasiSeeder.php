<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Freeform;
use App\Models\Kecamatan;
use App\Models\Kehadiran;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Nilaikonversi;
use App\Models\Pjdesa;
use App\Models\Saran;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Simulasi lengkap: 3 lokasi program, 3 PT, 1 DPL + 1 kelompok (1 ketua + 3 anggota) per PT per lokasi,
 * beserta kehadiran, log harian/bulanan, capaian KPI, laporan DPL, dan penilaian.
 * Jalankan: php artisan migrate:fresh --seed
 */
class SimulasiSeeder extends Seeder
{
    private const DOMAIN = '@kknt.test';

    private const ANGGOTA_PER_KELOMPOK = 3;

    private const HARI_KEHADIRAN = 14;

    // Lokasi => [kecamatan => [desa]]
    private const WILAYAH = [
        'Kota Bandung' => ['Coblong' => ['Dago', 'Lebakgede'], 'Sukajadi' => ['Pasteur', 'Cipedes']],
        'Kabupaten Bandung' => ['Dayeuhkolot' => ['Citeureup', 'Cangkuang Wetan'], 'Baleendah' => ['Andir', 'Manggahang']],
        'Kota Cimahi' => ['Cimahi Tengah' => ['Baros', 'Setiamanah'], 'Cimahi Utara' => ['Cibabat', 'Pasirkaliki']],
    ];

    // Rentang realisasi (% target) isian selesai per lokasi, agar capaian per daerah berbeda
    private const FAKTOR_LOKASI = [
        'Kota Bandung' => [80, 130],
        'Kabupaten Bandung' => [50, 100],
        'Kota Cimahi' => [20, 70],
    ];

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

    private string $password;

    private array $akun = [];

    public function run(string $password): void
    {
        $this->password = $password;
        mt_srand(2026);
        fake()->seed(2026);

        $wilayah = $this->wilayah();
        $targets = $this->kpi();
        $pts = $this->perguruanTinggi();

        $this->user('admin', 'admin'.self::DOMAIN, 'Administrator');
        $this->user('kepala', 'kepala'.self::DOMAIN, 'Kepala LLDIKTI');

        foreach ($pts as $i => $pt) {
            $this->user('pt', $pt->npsn, $pt->nm_lemb, ket: 'Login memakai NPSN');

            foreach ($wilayah as $data) {
                $desa = $data['desa'][$i % $data['desa']->count()];
                $this->kelompok($pt, $i + 1, $data['lokasi'], $desa, $targets);
            }
        }

        $this->belumPilihLokasi($pts->first());
        $this->aktivitas($targets);

        $this->command?->table(['Role', 'Login', 'Nama', 'Keterangan'], $this->akun);
    }

    private function wilayah(): array
    {
        $hasil = [];
        foreach (self::WILAYAH as $namaLokasi => $kecamatanList) {
            $lokasi = LokasiProgram::create(['nama_lokasi' => $namaLokasi]);
            $desa = collect();
            foreach ($kecamatanList as $namaKecamatan => $desaList) {
                $kecamatan = Kecamatan::create(['kecamatan' => $namaKecamatan]);
                foreach ($desaList as $namaDesa) {
                    $desa->push(Desa::create(['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $namaDesa]));
                }
            }
            $hasil[$namaLokasi] = ['lokasi' => $lokasi, 'desa' => $desa];
        }

        return $hasil;
    }

    private function kpi(): Collection
    {
        foreach (self::KPI as $nama => $kegiatan) {
            $kpi = Kpi::create(['nama_kpi' => $nama]);
            foreach ($kegiatan as [$namaKegiatan, $target, $satuan]) {
                Kpitarget::create(['id_kpi' => $kpi->id_kpi, 'kegiatan' => $namaKegiatan, 'target' => $target, 'satuan' => $satuan]);
            }
        }

        return Kpitarget::all();
    }

    private function perguruanTinggi(): Collection
    {
        return collect(['Universitas Padjadjaran Simulasi', 'Institut Teknologi Simulasi', 'Universitas Pasundan Simulasi'])
            ->map(fn ($nama, $i) => Satuanpendidikan::factory()->create([
                'nm_lemb' => $nama,
                'npsn' => '04100'.($i + 1),
            ]));
    }

    private function kelompok(Satuanpendidikan $pt, int $noPt, LokasiProgram $lokasi, Desa $desa, Collection $targets): void
    {
        $suffix = '.pt'.$noPt.'.'.Str::slug($lokasi->nama_lokasi, '').self::DOMAIN;

        $dpl = Dpl::factory()->create([
            'email' => 'dpl'.$suffix,
            'kodept' => $pt->npsn,
            'location_program' => $lokasi->id,
        ]);
        $this->user('dpl', $dpl->email, $dpl->nama, $lokasi->id, ket: $pt->nm_lemb.' – '.$lokasi->nama_lokasi);

        $prefixes = collect(['ketua'])->merge(collect(range(1, self::ANGGOTA_PER_KELOMPOK))->map(fn ($n) => 'mhs'.$n));
        foreach ($prefixes as $prefix) {
            $isKetua = $prefix === 'ketua';
            $mhs = Mahasiswa::factory()->create([
                'email' => $prefix.$suffix,
                'kodept' => $pt->npsn,
                'location_program' => $lokasi->id,
            ]);
            $this->user('mahasiswa', $mhs->email, $mhs->nama, $lokasi->id, $isKetua ? 'pjdesa' : null,
                ($isKetua ? 'Ketua kelompok' : 'Anggota').' – Desa '.$desa->desa);

            Mahasiswa_lokasi::create([
                'tahun' => (int) date('Y'),
                'id_mahasiswa' => $mhs->id_mahasiswa,
                'id_desa' => $desa->id_desa,
                'user_in_up' => $mhs->email,
            ]);
            Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);

            if ($isKetua) {
                $pj = Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
                foreach ($targets as $target) {
                    $this->capaian($pj, $target, self::FAKTOR_LOKASI[$lokasi->nama_lokasi]);
                }
            }
        }
    }

    private function belumPilihLokasi(Satuanpendidikan $pt): void
    {
        foreach ([1, 2] as $n) {
            $mhs = Mahasiswa::factory()->create([
                'email' => 'belumlokasi'.$n.'.pt1'.self::DOMAIN,
                'kodept' => $pt->npsn,
            ]);
            $this->user('mahasiswa', $mhs->email, $mhs->nama, ket: 'Belum memilih lokasi');
        }
    }

    private function capaian(Pjdesa $pj, Kpitarget $target, array $faktor): void
    {
        $acak = mt_rand(1, 100);
        if ($acak > 85) {
            return; // belum mengisi
        }

        [$status, $persen] = match (true) {
            $acak <= 60 => ['Y', mt_rand(...$faktor)],
            $acak <= 75 => ['P', mt_rand(10, 60)],
            default => ['N', 0],
        };

        Kpicapaian::factory()->create([
            'id_kpi' => $target->id_kpi,
            'id_target' => $target->id_target,
            'id_pjdesa' => $pj->id_pjdesa,
            'email' => $pj->email,
            'realisasi' => round((float) $target->target * $persen / 100),
            'satuan' => $target->satuan,
            'status_capaian' => $status,
        ]);
    }

    private function aktivitas(Collection $targets): void
    {
        $kpiIds = $targets->pluck('id_kpi')->unique()->values();
        $mentoring = Dplmentoring::pluck('email_dpl', 'email_mahasiswa');

        foreach (Mahasiswa::whereIn('email', $mentoring->keys())->get() as $mhs) {
            $emailDpl = $mentoring[$mhs->email];

            foreach (range(self::HARI_KEHADIRAN, 1) as $hari) {
                $tanggal = Carbon::today()->subDays($hari);
                if ($tanggal->isWeekend()) {
                    continue;
                }

                if (mt_rand(1, 100) <= 10) {
                    $this->kehadiran($mhs->email, $tanggal, fake()->randomElement(['izin', 'sakit']));

                    continue;
                }

                $this->kehadiran($mhs->email, $tanggal, 'hadir');
                if (mt_rand(1, 100) <= 75) {
                    Logkegiatan::factory()->create([
                        'email' => $mhs->email,
                        'tanggal' => $tanggal->toDateString(),
                        'id_kpi' => $kpiIds->random(),
                    ]);
                }
            }

            // Hari ini: anggota 1 izin (tombol log harian nonaktif), ketua sudah absen masuk
            if (str_starts_with($mhs->email, 'mhs1.')) {
                $this->kehadiran($mhs->email, Carbon::today(), 'izin');
            } elseif (str_starts_with($mhs->email, 'ketua.')) {
                $this->kehadiran($mhs->email, Carbon::today(), 'hadir', pulang: false);
            }

            foreach ([2, 1] as $offset) {
                $bulan = Carbon::now()->subMonthsNoOverflow($offset);
                Logbulanan::factory()->create(['email' => $mhs->email, 'tahun' => $bulan->year, 'bulan' => $bulan->month]);
            }

            Tugasakhir::factory()->create(['email' => $mhs->email, 'email_dpl' => $emailDpl]);
            Nilaikonversi::factory()->count(2)->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => $emailDpl]);
            Freeform::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => $emailDpl]);
        }

        foreach (Dpl::all() as $dpl) {
            foreach ([1, 0] as $offset) {
                $bulan = Carbon::now()->subMonthsNoOverflow($offset);
                Dpllaporan::factory()->create(['email' => $dpl->email, 'tahun' => $bulan->year, 'bulan' => $bulan->month]);
            }
        }

        $pertanyaan = Evaluasikegiatan::factory()->count(3)->create();
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

    private function kehadiran(string $email, Carbon $tanggal, string $status, bool $pulang = true): void
    {
        $hadir = $status === 'hadir';

        Kehadiran::factory()->create([
            'email' => $email,
            'tanggal' => $tanggal->toDateString(),
            'status_kehadiran' => $status,
            'waktu_masuk' => $hadir ? $tanggal->copy()->setTime(8, mt_rand(0, 30)) : null,
            'waktu_pulang' => $hadir && $pulang ? $tanggal->copy()->setTime(16, mt_rand(0, 30)) : null,
            'keterangan' => $hadir ? null : ucfirst($status).' – '.fake()->sentence(4),
        ]);
    }

    private function user(string $role, string $email, string $nama, ?string $lokasi = null, ?string $akses = null, string $ket = ''): void
    {
        User::factory()->role($role)->withPassword($this->password)->create([
            'name' => $nama,
            'email' => $email,
            'location_program' => $lokasi,
            'akses' => $akses,
        ]);

        $this->akun[] = [$role, $email, $nama, $ket];
    }
}
