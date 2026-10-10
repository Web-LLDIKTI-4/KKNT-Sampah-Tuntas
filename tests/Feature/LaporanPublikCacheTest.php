<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\CapaianKegiatan;
use App\Models\PenguranganSampah;
use App\Models\PendataanPemilahanSampah;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanPublikCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function wilayahDanSampah(string $nama, string $bulan): Kecamatan
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Lokasi '.$nama]);
        $kecamatan = Kecamatan::factory()->create(['kecamatan' => 'Kec '.$nama]);
        $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Desa '.$nama]);
        $pt = Satuanpendidikan::factory()->create();
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email]);
        PendataanPemilahanSampah::factory()->create([
            'email' => $mhs->email, 'tanggal' => $bulan.'-01',
            'organik_kg' => 20, 'anorganik_kg' => 0, 'residu_kg' => 80,
        ]);

        return $kecamatan;
    }

    public function test_reseed_after_cache_warm_gives_200_not_422(): void
    {
        $bulanLama = now()->subMonths(2)->format('Y-m');
        $bulanBaru = now()->format('Y-m');
        $lama = $this->wilayahDanSampah('Lama', $bulanLama);

        // Cache terisi: halaman awal + bulanList
        $this->get('/login')->assertOk()->assertSee('Kec Lama');
        $this->get('/login/laporan?bulan='.$bulanLama.'&kecamatan='.$lama->id_kecamatan)->assertOk();

        // Seed ulang: tabel dikosongkan tanpa event (seperti migrate:fresh), data baru dibuat lewat model
        foreach (['pendataan_pemilahan_sampah', 'logkegiatan', 'mahasiswa_lokasi', 'pj_desa', 'mahasiswa', 'desa', 'kecamatan'] as $tabel) {
            DB::table($tabel)->delete();
        }
        $baru = $this->wilayahDanSampah('Baru', $bulanBaru);

        $this->get('/login')->assertOk()->assertSee('Kec Baru')->assertDontSee('Kec Lama');
        $this->get('/login/laporan?bulan='.$bulanBaru.'&kecamatan='.$baru->id_kecamatan)->assertOk();
    }

    public function test_capaiankegiatan_change_resets_public_cache_version(): void
    {
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY);
        $capaian = CapaianKegiatan::factory()->create(['email' => 'ketua@uji.test']);
        $setelahSimpan = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY);
        $this->assertNotSame($versi, $setelahSimpan);

        $capaian->delete();
        $this->assertNotSame($setelahSimpan, Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY));
    }

    public function test_pendataan_pemilahan_change_resets_public_cache_version(): void
    {
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY);
        $log = PendataanPemilahanSampah::factory()->create();
        $setelahSimpan = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY);
        $this->assertNotSame($versi, $setelahSimpan);

        $log->delete();
        $this->assertNotSame($setelahSimpan, Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY));
    }

    public function test_forget_public_cache_resets_bulan_list_and_version(): void
    {
        Cache::put(PenguranganSampah::PUBLIC_BULAN_CACHE_KEY, ['2020-01'], 600);
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY);

        // Dipanggil DatabaseSeeder & BebanSeeder (insert massal tanpa event model)
        PenguranganSampah::forgetPublicCache();

        $this->assertFalse(Cache::has(PenguranganSampah::PUBLIC_BULAN_CACHE_KEY));
        $this->assertNotSame($versi, Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY));
    }
}
