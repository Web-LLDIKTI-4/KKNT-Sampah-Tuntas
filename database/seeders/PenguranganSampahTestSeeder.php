<?php

namespace Database\Seeders;

use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\KategoriKegiatan;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PenguranganSampah;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\PenguranganSampahService;
use Illuminate\Database\Seeder;

/**
 * Uji 1 data sampah per ketua per bulan: 1 PT, 3 kelompok (3 ketua) di 1 kelurahan, bulan berjalan.
 * Aman dijalankan ulang (firstOrCreate/updateOrCreate per kunci alami).
 * Jalankan: php artisan db:seed --class=PenguranganSampahTestSeeder
 */
class PenguranganSampahTestSeeder extends Seeder
{
    private const DOMAIN = '@sampahtest.test';

    private const NPSN = '049901';

    // Harapan PT: pengurangan 600 / timbulan 3000 = 20,00% (hijau); ketaatan 120 / 300 = 40,00%
    private const KETUA = [
        // [timbulan, organik, anorganik, jml_rumah, jml_rumah_memilah]
        [1000, 100, 50, 100, 30],   // 15%
        [1000, 150, 50, 100, 40],   // 20%
        [1000, 200, 50, 100, 50],   // 25%
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('PenguranganSampahTestSeeder dilewati: tidak boleh dijalankan di production.');

            return;
        }

        $password = config('app.seed_password') ?: 'password';
        $bulan = now()->startOfMonth()->toDateString();

        $lokasi = LokasiProgram::firstOrCreate(['nama_lokasi' => 'Lokasi Uji Sampah']);
        $kecamatan = Kecamatan::firstOrCreate(['kecamatan' => 'Kecamatan Uji Sampah']);
        $desa = Desa::firstOrCreate(['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Kelurahan Uji Sampah']);
        $kategori = KategoriKegiatan::firstOrCreate(['nama_kategori' => 'Pengurangan Sampah Rumah Tangga']);
        $pt = Satuanpendidikan::where('npsn', self::NPSN)->first()
            ?? Satuanpendidikan::factory()->create(['npsn' => self::NPSN, 'nm_lemb' => 'Universitas Uji Sampah']);

        foreach (self::KETUA as $i => [$timbulan, $organik, $anorganik, $rumah, $memilah]) {
            $email = 'ketua'.($i + 1).self::DOMAIN;

            $mhs = Mahasiswa::where('email', $email)->first() ?? Mahasiswa::factory()->create([
                'email' => $email,
                'kodept' => $pt->npsn,
                'location_program' => $lokasi->id,
            ]);
            User::where('email', $email)->exists() || User::factory()->role('mahasiswa')->withPassword($password)->create([
                'name' => $mhs->nama,
                'email' => $email,
                'location_program' => $lokasi->id,
                'akses' => 'pjdesa',
            ]);
            Mahasiswa_lokasi::updateOrCreate(
                ['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa],
                ['id_desa' => $desa->id_desa, 'user_in_up' => $email],
            );
            $pj = Pjdesa::firstOrCreate(['email' => $email], ['id_desa' => $desa->id_desa]);

            CapaianKegiatan::updateOrCreate(
                ['email' => $email, 'bulan' => $bulan],
                CapaianKegiatan::factory()->make(['id_kategori' => $kategori->id_kategori, 'id_pjdesa' => $pj->id_pjdesa, 'status_capaian' => 'Y'])
                    ->only(['id_kategori', 'id_pjdesa', 'status_capaian', 'tautan', 'permasalahan', 'solusi', 'kendala']),
            );

            PenguranganSampah::updateOrCreate(
                ['email' => $email, 'bulan' => $bulan],
                PenguranganSampahService::hitung([
                    'id_pjdesa' => $pj->id_pjdesa,
                    'id_desa' => $desa->id_desa,
                    'jml_rw' => 5,
                    'jml_penduduk' => $rumah * 4,
                    'jml_rumah' => $rumah,
                    'jml_rumah_memilah' => $memilah,
                    'timbulan' => $timbulan,
                    'organik_sumber' => $organik,
                    'organik_metode_unit' => 1,
                    'organik_dlh' => 0,
                    'anorganik_sumber' => $anorganik,
                    'anorganik_metode_unit' => 1,
                ])
            );
        }

        $total = app(PenguranganSampahService::class)->totalPer('kodept', ['bulan' => now()->format('Y-m')])[self::NPSN] ?? null;
        $this->command?->info('Persen pengurangan PT uji: '.PenguranganSampah::formatPersen($total?->persen_pengurangan).' (harapan 20,00%)');
    }
}
