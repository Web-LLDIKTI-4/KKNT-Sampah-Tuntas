<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Services\KpiSampahService;
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

        Kpisampah::create(KpiSampahService::hitung([
            'email' => $mhs->email, 'id_desa' => $desa->id_desa, 'bulan' => $bulan.'-01',
            'jml_rw_kbs' => 1, 'jml_rw_non_kbs' => 1, 'jml_rumah' => 100, 'jml_rumah_memilah' => 50,
            'timbulan' => 100, 'pengurangan_organik' => 20, 'pengurangan_anorganik' => 0, 'residu' => 80, 'jml_bank_sampah' => 0,
        ]));

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
        foreach (['kpi_sampah', 'pj_desa', 'mahasiswa', 'desa', 'kecamatan'] as $tabel) {
            DB::table($tabel)->delete();
        }
        $baru = $this->wilayahDanSampah('Baru', $bulanBaru);

        $this->get('/login')->assertOk()->assertSee('Kec Baru')->assertDontSee('Kec Lama');
        $this->get('/login/laporan?bulan='.$bulanBaru.'&kecamatan='.$baru->id_kecamatan)->assertOk();
    }

    public function test_kpicapaian_change_resets_public_cache_version(): void
    {
        $versi = Cache::get(Kpisampah::PUBLIC_VERSION_CACHE_KEY);
        $capaian = Kpicapaian::factory()->create(['email' => 'ketua@uji.test']);
        $setelahSimpan = Cache::get(Kpisampah::PUBLIC_VERSION_CACHE_KEY);
        $this->assertNotSame($versi, $setelahSimpan);

        $capaian->delete();
        $this->assertNotSame($setelahSimpan, Cache::get(Kpisampah::PUBLIC_VERSION_CACHE_KEY));
    }

    public function test_forget_public_cache_resets_bulan_list_and_version(): void
    {
        Cache::put(Kpisampah::PUBLIC_BULAN_CACHE_KEY, ['2020-01'], 600);
        $versi = Cache::get(Kpisampah::PUBLIC_VERSION_CACHE_KEY);

        // Dipanggil DatabaseSeeder & BebanSeeder (insert massal tanpa event model)
        Kpisampah::forgetPublicCache();

        $this->assertFalse(Cache::has(Kpisampah::PUBLIC_BULAN_CACHE_KEY));
        $this->assertNotSame($versi, Cache::get(Kpisampah::PUBLIC_VERSION_CACHE_KEY));
    }
}
