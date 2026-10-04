<?php

namespace Database\Seeders;

use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\LokasiProgram;
use App\Services\KpiSampahService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Data uji beban: 20.000 mahasiswa (200 PT x 100), tetap 1 PT = 1 kelurahan (200 kelurahan baru),
 * kelompok 5 orang (1 ketua), 5 DPL per PT, log harian & kehadiran hari kerja 30 hari terakhir,
 * data sampah 3 bulan per kelurahan, dan isian capaian KPI ketua.
 * Insert massal per potongan agar cepat; password di-hash sekali untuk semua akun.
 * Jalankan setelah SimulasiSeeder: php artisan db:seed --class=BebanSeeder
 */
class BebanSeeder extends Seeder
{
    private const DOMAIN = '@beban.test';

    private const JUMLAH_PT = 200;

    private const MHS_PER_PT = 100;

    private const ANGGOTA_PER_KELOMPOK = 5;

    private const DPL_PER_PT = 5;

    private const KELURAHAN_PER_KECAMATAN = 4;

    private const HARI_LOG = 30;

    private const POTONGAN = 2000;

    private string $passwordHash;

    private string $sekarang;

    private array $antrian = [];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Seeder dummy tidak boleh dijalankan di production.');
        }

        mt_srand(2026);
        $mulai = microtime(true);
        $password = config('app.seed_password') ?: Str::password(12, symbols: false);
        $this->passwordHash = Hash::make($password);
        $this->sekarang = now()->toDateTimeString();

        $lokasi = LokasiProgram::orderBy('nama_lokasi')->pluck('id')->all() ?: [LokasiProgram::create(['nama_lokasi' => 'Kota Bandung'])->id];
        $kpi = Kpi::pluck('id_kpi')->all() ?: [Kpi::create(['nama_kpi' => 'Pengurangan Sampah Rumah Tangga'])->id_kpi];
        $kelurahan = $this->wilayah();
        $hariKerja = $this->hariKerja();

        foreach (range(1, self::JUMLAH_PT) as $noPt) {
            $this->perguruanTinggi($noPt, $lokasi[$noPt % count($lokasi)], $kelurahan[$noPt - 1], $kpi, $hariKerja);
        }
        $this->flushSemua();

        $this->command?->info(sprintf(
            'Data beban: %s mahasiswa, %s kelompok, %s log harian, %s kehadiran (%.0f detik).',
            number_format(DB::table('mahasiswa')->where('email', 'like', '%'.self::DOMAIN)->count()),
            number_format(DB::table('pj_desa')->where('email', 'like', '%'.self::DOMAIN)->count()),
            number_format(DB::table('logkegiatan')->where('email', 'like', '%'.self::DOMAIN)->count()),
            number_format(DB::table('kehadiran')->where('email', 'like', '%'.self::DOMAIN)->count()),
            microtime(true) - $mulai,
        ));
        if (! config('app.seed_password')) {
            $this->command?->warn('Password semua akun beban: '.$password);
        }
    }

    // 200 kelurahan baru, 4 per kecamatan
    private function wilayah(): array
    {
        $kelurahan = [];
        foreach (range(1, (int) ceil(self::JUMLAH_PT / self::KELURAHAN_PER_KECAMATAN)) as $k) {
            $idKecamatan = (string) Str::uuid7();
            $this->tambah('kecamatan', ['id_kecamatan' => $idKecamatan, 'kecamatan' => sprintf('Kecamatan Beban %03d', $k)] + $this->waktu());
            foreach (range(1, self::KELURAHAN_PER_KECAMATAN) as $d) {
                $idDesa = (string) Str::uuid7();
                $this->tambah('desa', ['id_desa' => $idDesa, 'id_kecamatan' => $idKecamatan, 'desa' => sprintf('Kelurahan Beban %03d-%d', $k, $d)] + $this->waktu());
                $kelurahan[] = $idDesa;
            }
        }
        $this->flushSemua();

        return $kelurahan;
    }

    private function perguruanTinggi(int $noPt, string $lokasi, string $idDesa, array $kpi, array $hariKerja): void
    {
        $npsn = sprintf('05%04d', $noPt);
        $this->tambah('ref_satuanpendidikan', [
            'id_sp' => (string) Str::uuid7(), 'nm_lemb' => 'Perguruan Tinggi Beban '.$noPt, 'npsn' => $npsn,
            'nm_singkat' => 'PTB'.$noPt, 'kota_kabupaten' => 'Kota Bandung', 'provinsi' => 'Jawa Barat', 'stat_sp' => 'A',
            // Format sama dengan data PDDIKTI; accessor Satuanpendidikan::last_update wajib terisi
            'last_update' => now()->format('M d Y h:i:s:A'),
        ]);
        $this->user('pt', $npsn, 'Perguruan Tinggi Beban '.$noPt, null);

        $dpl = [];
        foreach (range(1, self::DPL_PER_PT) as $n) {
            $email = 'dpl'.$n.'.pt'.$noPt.self::DOMAIN;
            $this->tambah('dpl', [
                'id_dpl' => (string) Str::uuid7(), 'nidn' => sprintf('04%04d%04d', $noPt, $n), 'nama' => fake()->name().', M.T.',
                'email' => $email, 'location_program' => $lokasi, 'prodi' => 'Teknik Lingkungan', 'kodept' => $npsn,
            ] + $this->waktu());
            $this->user('dpl', $email, 'DPL '.$n.' PT '.$noPt, $lokasi);
            $dpl[] = $email;
        }

        $ketuaPertama = null;
        foreach (range(1, self::MHS_PER_PT) as $n) {
            $email = 'mhs'.$n.'.pt'.$noPt.self::DOMAIN;
            $isKetua = ($n - 1) % self::ANGGOTA_PER_KELOMPOK === 0;
            $idMahasiswa = (string) Str::uuid7();

            $this->tambah('mahasiswa', [
                'id_mahasiswa' => $idMahasiswa, 'nim' => sprintf('%03d%05d', $noPt, $n), 'tahun_masuk' => 2023,
                'nama' => fake()->name(), 'email' => $email, 'location_program' => $lokasi,
                'prodi' => 'Teknik Lingkungan', 'kodept' => $npsn,
            ] + $this->waktu());
            $this->user('mahasiswa', $email, 'Mahasiswa '.$n.' PT '.$noPt, $lokasi, $isKetua ? 'pjdesa' : null);
            $this->tambah('mahasiswa_lokasi', [
                'id_lokasi' => (string) Str::uuid7(), 'tahun' => (int) date('Y'), 'id_mahasiswa' => $idMahasiswa,
                'id_desa' => $idDesa, 'user_in_up' => $email,
            ] + $this->waktu());
            $this->tambah('dpl_mentoring', [
                'id_mentoring' => (string) Str::uuid7(), 'email_mahasiswa' => $email, 'email_dpl' => $dpl[$n % count($dpl)],
            ] + $this->waktu());

            if ($isKetua) {
                $idPj = (string) Str::uuid7();
                $this->tambah('pj_desa', ['id_pjdesa' => $idPj, 'email' => $email, 'id_desa' => $idDesa] + $this->waktu());
                $ketuaPertama ??= [$idPj, $email];
                // UNIQUE(email, bulan): tiap KPI di bulan berbeda, mundur dari bulan ini
                foreach ($kpi as $mundur => $idKpi) {
                    if (mt_rand(1, 100) <= 85) {
                        $this->tambah('kpi_capaian', [
                            'id_capaian' => (string) Str::uuid7(), 'id_kpi' => $idKpi, 'id_pjdesa' => $idPj, 'email' => $email,
                            'bulan' => now()->startOfMonth()->subMonths($mundur)->toDateString(),
                            'status_capaian' => array_keys(Kpicapaian::STATUS)[mt_rand(0, 2)], 'tautan' => 'https://drive.google.com/beban',
                            'permasalahan' => 'Warga belum rutin memilah sampah', 'solusi' => 'Sosialisasi door to door',
                            'kendala' => 'Tempat sampah terpilah',
                        ] + $this->waktu());
                    }
                }
            }

            foreach ($hariKerja as $tanggal) {
                $this->tambah('logkegiatan', [
                    'id_log' => (string) Str::uuid7(), 'email' => $email, 'tanggal' => $tanggal,
                    'deskripsi' => 'Pendampingan pemilahan sampah warga', 'volume' => (string) mt_rand(1, 20),
                    'satuan' => 'kegiatan', 'id_kpi' => $kpi[mt_rand(0, count($kpi) - 1)], 'tautan' => null,
                ] + $this->waktu());
                $this->tambah('kehadiran', [
                    'id_kehadiran' => (string) Str::uuid7(), 'email' => $email, 'tanggal' => $tanggal, 'status_kehadiran' => 'hadir',
                    'waktu_masuk' => $tanggal.' 08:0'.mt_rand(0, 9).':00', 'waktu_pulang' => $tanggal.' 16:0'.mt_rand(0, 9).':00',
                    'latitude_datang' => -6.899248, 'longitude_datang' => 107.63772,
                    'latitude_pulang' => -6.899248, 'longitude_pulang' => 107.63772, 'keterangan' => null,
                ] + $this->waktu());
            }
        }

        $this->dataSampah($idDesa, $ketuaPertama);
    }

    // 3 bulan terakhir; sebaran persentase lebar agar klaster hijau/kuning/merah terisi
    private function dataSampah(string $idDesa, array $ketua): void
    {
        [$idPj, $email] = $ketua;
        $rumah = mt_rand(200, 800);
        foreach ([3, 2, 1] as $mundur) {
            $timbulan = $rumah * mt_rand(12, 18);
            $organik = round($timbulan * mt_rand(3, 30) / 100, 2);
            $anorganik = round($timbulan * mt_rand(2, 10) / 100, 2);

            $this->tambah('kpi_sampah', KpiSampahService::hitung([
                'id_sampah' => (string) Str::uuid7(), 'id_pjdesa' => $idPj, 'email' => $email, 'id_desa' => $idDesa,
                'bulan' => now()->startOfMonth()->subMonths($mundur)->toDateString(),
                'jml_rw_kbs' => mt_rand(1, 5), 'jml_rw_non_kbs' => mt_rand(2, 8), 'jml_rumah' => $rumah,
                'jml_rumah_memilah' => (int) round($rumah * mt_rand(20, 80) / 100), 'timbulan' => $timbulan,
                'pengurangan_organik' => $organik, 'pengurangan_anorganik' => $anorganik,
                'residu' => round($timbulan - $organik - $anorganik, 2), 'jml_bank_sampah' => mt_rand(0, 3),
            ]) + $this->waktu());
        }
    }

    private function user(string $role, string $email, string $nama, ?string $lokasi, ?string $akses = null): void
    {
        $this->tambah('users', [
            'id' => (string) Str::uuid7(), 'name' => $nama, 'email' => $email, 'location_program' => $lokasi,
            'password' => $this->passwordHash, 'role' => $role, 'akses' => $akses,
        ] + $this->waktu());
    }

    private function hariKerja(): array
    {
        return collect(range(self::HARI_LOG, 1))
            ->map(fn ($hari) => Carbon::today()->subDays($hari))
            ->reject(fn (Carbon $t) => $t->isWeekend())
            ->map(fn (Carbon $t) => $t->toDateString())
            ->values()->all();
    }

    private function waktu(): array
    {
        return ['created_at' => $this->sekarang, 'updated_at' => $this->sekarang];
    }

    private function tambah(string $tabel, array $baris): void
    {
        $this->antrian[$tabel][] = $baris;
        if (count($this->antrian[$tabel]) >= self::POTONGAN) {
            $this->flush($tabel);
        }
    }

    private function flush(string $tabel): void
    {
        if (! empty($this->antrian[$tabel])) {
            DB::table($tabel)->insert($this->antrian[$tabel]);
            $this->antrian[$tabel] = [];
        }
    }

    // Urutan induk dulu tidak wajib (tanpa foreign key), tetapi dijaga agar konsisten
    private function flushSemua(): void
    {
        foreach (array_keys($this->antrian) as $tabel) {
            $this->flush($tabel);
        }
    }
}
