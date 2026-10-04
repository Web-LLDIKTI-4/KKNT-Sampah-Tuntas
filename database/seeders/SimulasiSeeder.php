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
use RuntimeException;

/**
 * Simulasi lengkap: 3 lokasi program, 12 kelurahan, 12 PT; 1 PT hanya di 1 kelurahan dan 1 kelurahan hanya 1 PT.
 * Per PT: 1 DPL + 1 kelompok (1 ketua + 3 anggota) + 2 mahasiswa tambahan, semuanya di kelurahan PT tersebut,
 * beserta kehadiran, log harian/bulanan, capaian KPI, data sampah bulanan 3 bulan terakhir, laporan DPL, dan penilaian.
 * Jalankan: php artisan migrate:fresh --seed && php artisan db:seed --class=SimulasiSeeder
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

    private const KPI = ['Pengurangan Sampah Rumah Tangga', 'Bank Sampah', 'Pengolahan Sampah Organik'];

    private string $password;

    private array $akun = [];

    public function run(?string $password = null): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Seeder dummy tidak boleh dijalankan di production.');
        }

        $this->password = $password ?? (config('app.seed_password') ?: Str::password(12, symbols: false));
        if ($password === null) {
            $this->command?->warn('Password semua akun dummy: '.$this->password);
        }
        mt_srand(2026);
        fake()->seed(2026);

        $wilayah = $this->wilayah();
        $kpis = $this->kpi();
        $pts = $this->perguruanTinggi();

        $this->user('admin', 'admin'.self::DOMAIN, 'Administrator');
        $this->user('kepala', 'kepala'.self::DOMAIN, 'Kepala LLDIKTI');

        // Pasangan PT <-> kelurahan 1:1, urut lokasi -> kecamatan -> kelurahan
        $penempatan = $this->penempatan($wilayah);
        foreach ($pts as $i => $pt) {
            $this->user('pt', $pt->npsn, $pt->nm_lemb, ket: 'Login memakai NPSN');

            [$lokasi, $desa] = $penempatan[$i];
            $this->kelompok($pt, $i + 1, $lokasi, $desa, $kpis);
        }

        $this->belumPilihLokasi($pts->first());
        $this->aktivitas($kpis);
        $this->sebaranMahasiswa($pts, $penempatan);
        $this->call(KpiSampahSeeder::class);

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

    /**
     * @return array<int, array{0: LokasiProgram, 1: Desa}>
     */
    private function penempatan(array $wilayah): array
    {
        $hasil = [];
        foreach ($wilayah as $data) {
            foreach ($data['desa'] as $desa) {
                $hasil[] = [$data['lokasi'], $desa];
            }
        }

        return $hasil;
    }

    private function kpi(): Collection
    {
        return collect(self::KPI)->map(fn ($nama) => Kpi::create(['nama_kpi' => $nama]));
    }

    private function perguruanTinggi(): Collection
    {
        // Jumlah PT = jumlah kelurahan di WILAYAH agar setiap PT mendapat tepat 1 kelurahan
        return collect([
            'Universitas Padjadjaran Simulasi',
            'Institut Teknologi Simulasi',
            'Universitas Pasundan Simulasi',
            'Universitas Islam Bandung Simulasi',
            'Politeknik Negeri Simulasi',
            'Universitas Telkom Simulasi',
            'Universitas Katolik Parahyangan Simulasi',
            'Universitas Komputer Indonesia Simulasi',
            'Universitas Jenderal Achmad Yani Simulasi',
            'Institut Teknologi Nasional Simulasi',
            'Universitas Widyatama Simulasi',
            'Universitas Langlangbuana Simulasi',
        ])
            ->map(fn ($nama, $i) => Satuanpendidikan::factory()->create([
                'nm_lemb' => $nama,
                'npsn' => sprintf('041%03d', $i + 1),
            ]));
    }

    private function kelompok(Satuanpendidikan $pt, int $noPt, LokasiProgram $lokasi, Desa $desa, Collection $kpis): void
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
                foreach ($kpis as $kpi) {
                    $this->capaian($pj, $kpi);
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

    // Mahasiswa tambahan, tetap di kelurahan PT-nya (1 PT = 1 kelurahan)
    private function sebaranMahasiswa(Collection $pts, array $penempatan): void
    {
        foreach ($pts as $i => $pt) {
            [$lokasi, $desa] = $penempatan[$i];
            foreach ([1, 2] as $n) {
                $mhs = Mahasiswa::factory()->create([
                    'email' => 'sebaran'.$n.'.pt'.($i + 1).'.'.Str::slug($lokasi->nama_lokasi, '').self::DOMAIN,
                    'kodept' => $pt->npsn,
                    'location_program' => $lokasi->id,
                ]);

                Mahasiswa_lokasi::create([
                    'tahun' => (int) date('Y'),
                    'id_mahasiswa' => $mhs->id_mahasiswa,
                    'id_desa' => $desa->id_desa,
                    'user_in_up' => $mhs->email,
                ]);
            }
        }
    }

    // Isian permasalahan/solusi per KPI; sebagian ketua belum mengisi
    private function capaian(Pjdesa $pj, Kpi $kpi): void
    {
        $acak = mt_rand(1, 100);
        if ($acak > 85) {
            return;
        }

        Kpicapaian::factory()->create([
            'id_kpi' => $kpi->id_kpi,
            'id_pjdesa' => $pj->id_pjdesa,
            'email' => $pj->email,
            'status_capaian' => $acak <= 60 ? 'Y' : ($acak <= 75 ? 'P' : 'N'),
        ]);
    }

    private function aktivitas(Collection $kpis): void
    {
        $kpiIds = $kpis->pluck('id_kpi')->values();
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
        // Admin bisa sudah dibuat AdminSeeder; jangan bentrok/timpa
        if ($role === 'admin' && User::where('email', $email)->exists()) {
            $this->akun[] = [$role, $email, $nama, 'Sudah ada, tidak diubah'];

            return;
        }

        User::factory()->role($role)->withPassword($this->password)->create([
            'name' => $nama,
            'email' => $email,
            'location_program' => $lokasi,
            'akses' => $akses,
        ]);

        $this->akun[] = [$role, $email, $nama, $ket];
    }
}
