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
use Tests\TestCase;

class CapaianPublikTest extends TestCase
{
    use RefreshDatabase;

    private string $bulan;

    private array $d = [];

    /**
     * Lokasi A / Kec Alfa: Desa Satu (Univ Hijau 25%, Univ Tanpa Data tanpa isian) + Desa Tiga (Univ Kuning 15%)
     * -> kecamatan & lokasi = 40/200 = 20,00%. Lokasi B / Kec Beta / Desa Dua: Univ Merah 5%. Total = 45/300 = 15%.
     * kpi_sampah unik per (id_desa, bulan): satu kelurahan hanya punya satu isian per bulan.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->bulan = now()->subMonth()->format('Y-m');

        $this->d['lokasiA'] = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Alfa']);
        $this->d['lokasiB'] = LokasiProgram::factory()->create(['nama_lokasi' => 'Kabupaten Beta']);
        $this->d['kecA'] = Kecamatan::factory()->create(['kecamatan' => 'Kec Alfa']);
        $this->d['kecB'] = Kecamatan::factory()->create(['kecamatan' => 'Kec Beta']);
        $this->d['desaA'] = Desa::factory()->create(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'desa' => 'Desa Satu']);
        $this->d['desaB'] = Desa::factory()->create(['id_kecamatan' => $this->d['kecB']->id_kecamatan, 'desa' => 'Desa Dua']);
        $this->d['desaC'] = Desa::factory()->create(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'desa' => 'Desa Tiga']);

        $this->ketua('hijau', 'Univ Hijau', $this->d['lokasiA'], $this->d['desaA'], 25);
        $this->ketua('kuning', 'Univ Kuning', $this->d['lokasiA'], $this->d['desaC'], 15);
        $this->ketua('merah', 'Univ Merah', $this->d['lokasiB'], $this->d['desaB'], 5);
        $this->ketua('kosong', 'Univ Tanpa Data', $this->d['lokasiA'], $this->d['desaA'], null);
    }

    private function ketua(string $key, string $namaPt, LokasiProgram $lokasi, Desa $desa, ?int $pengurangan): void
    {
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => $namaPt]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => 'Ketua '.$namaPt, 'location_program' => $lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
        $this->d['pt_'.$key] = $pt;
        $this->d['ketua_'.$key] = $mhs;
        if ($pengurangan === null) {
            return;
        }
        Kpisampah::create(KpiSampahService::hitung([
            'email' => $mhs->email, 'id_desa' => $desa->id_desa, 'bulan' => $this->bulan.'-01',
            'jml_rw_kbs' => 1, 'jml_rw_non_kbs' => 1, 'jml_rumah' => 100, 'jml_rumah_memilah' => 50,
            'timbulan' => 100, 'pengurangan_organik' => $pengurangan, 'pengurangan_anorganik' => 0, 'residu' => 100 - $pengurangan, 'jml_bank_sampah' => 0,
        ]));
    }

    private function publik(array $filter = []): array
    {
        return app(KpiSampahService::class)->drilldownPublik($filter + ['bulan' => null, 'id_kecamatan' => null, 'id_desa' => null, 'klaster' => null]);
    }

    public function test_persen_lokasi_is_cumulative_and_strict_threshold_makes_twenty_yellow(): void
    {
        $data = $this->publik();
        $lokasi = $data['kecamatan']->keyBy('nama_lokasi');

        $this->assertEquals(20.0, $lokasi['Kota Alfa']->persen);
        $this->assertSame('kuning', $lokasi['Kota Alfa']->klaster);
        $this->assertEquals(5.0, $lokasi['Kabupaten Beta']->persen);
        $this->assertSame('merah', $lokasi['Kabupaten Beta']->klaster);
        $this->assertSame('kuning', $lokasi['Kota Alfa']->kecamatan->first()->klaster);
        $this->assertEquals(15.0, $data['total_keseluruhan']->persen_pengurangan);

        $this->assertSame('hijau', Kpisampah::klaster(20.0));
        $this->assertSame('kuning', Kpisampah::klaster(20.0, true));
        $this->assertSame('hijau', Kpisampah::klaster(20.01, true));
        $this->assertSame('kuning', Kpisampah::klaster(10.0, true));
        $this->assertSame('merah', Kpisampah::klaster(9.99, true));
        $this->assertSame('table-success', Kpisampah::warnaSel(20.0));
        $this->assertSame('table-warning', Kpisampah::warnaSel(20.0, true));
    }

    public function test_total_keseluruhan_ignores_kecamatan_and_klaster_filter(): void
    {
        $data = $this->publik(['id_kecamatan' => $this->d['kecB']->id_kecamatan, 'klaster' => 'merah']);

        $this->assertEquals(15.0, $data['total_keseluruhan']->persen_pengurangan);
        // Publik tidak menghitung total per kecamatan (hemat 1 query)
        $this->assertSame($data['total_keseluruhan'], $data['total']);
        // Dashboard tetap: total terfilter kecamatan
        $dashboard = app(KpiSampahService::class)->drilldown(['bulan' => null, 'id_kecamatan' => $this->d['kecB']->id_kecamatan]);
        $this->assertEquals(5.0, $dashboard['total']->persen_pengurangan);
    }

    public function test_capaian_strict_only_reaches_hundred_above_target(): void
    {
        $this->assertEquals(99.99, Kpisampah::capaian(20.0, true));
        $this->assertEquals(100.0, Kpisampah::capaian(20.01, true));
        $this->assertEquals(50.0, Kpisampah::capaian(10.0, true));
        $this->assertNull(Kpisampah::capaian(null, true));
        // Dashboard tetap inklusif
        $this->assertEquals(100.0, Kpisampah::capaian(20.0));
    }

    public function test_persen_pt_is_per_pt_in_kelurahan_and_ketua_has_names_only(): void
    {
        $data = $this->publik(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa]);
        $kelompok = $data['kelompok']->keyBy('nama_pt');

        $this->assertEquals(25.0, $kelompok['Univ Hijau']->persen_pt);
        $this->assertSame('hijau', $kelompok['Univ Hijau']->klaster_pt);
        $this->assertNull($kelompok['Univ Tanpa Data']->persen_pt);
        $this->assertNull($kelompok['Univ Tanpa Data']->klaster_pt);
        $this->assertFalse(property_exists($kelompok['Univ Tanpa Data'], 'persen'));
        $this->assertArrayNotHasKey('Univ Kuning', $kelompok->all());
        $this->assertSame(['Ketua Univ Hijau'], $kelompok['Univ Hijau']->ketua->all());
        $this->assertSame(1, $kelompok['Univ Hijau']->jumlah_ketua);
        $this->assertSame(1, $kelompok['Univ Tanpa Data']->jumlah_ketua);
        $this->assertFalse(property_exists($kelompok['Univ Hijau'], 'phone'));

        $res = $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Pelaksana (PTS) di Kelurahan/Desa Desa Satu')
            ->assertSee('JML. MHS')->assertSee('JML. DPL')
            ->assertSee('Ketua Univ Hijau')->assertSee('ri-eye-line', false)
            ->assertSee('25,00%')
            ->assertDontSee($this->d['ketua_hijau']->phone)->assertDontSee($this->d['ketua_kosong']->phone);
        $this->assertStringContainsString('table-success', $res->getContent());
    }

    public function test_klaster_filters_rows_without_recalculating(): void
    {
        $merah = $this->publik(['klaster' => 'merah']);
        $this->assertSame(['Kabupaten Beta'], $merah['kecamatan']->pluck('nama_lokasi')->all());
        $this->assertSame('merah', $merah['params']['klaster']);
        $this->assertEquals(15.0, $merah['total_keseluruhan']->persen_pengurangan);

        $hijau = $this->publik(['klaster' => 'hijau']);
        $this->assertTrue($hijau['kecamatan']->isEmpty());

        $pt = $this->publik(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa, 'klaster' => 'hijau']);
        $this->assertSame(['Univ Hijau'], $pt['kelompok']->pluck('nama_pt')->all());
        $this->assertEquals(25.0, $pt['kelompok']->first()->persen_pt);

        $this->get('login/laporan?klaster=merah')->assertOk()->assertSee('Kec Beta')->assertDontSee('Kec Alfa');
        $this->get('login/laporan?klaster=hijau')->assertOk()->assertSee('Tidak ada kecamatan pada klaster ini');
    }

    public function test_jumlah_ketua_counts_ketua_per_pt_in_kelurahan_and_matches_dashboard(): void
    {
        $pt = $this->d['pt_hijau'];
        foreach ([['Ketua Dua', $this->d['desaA']], ['Ketua Lain Desa', $this->d['desaC']]] as [$nama, $desa]) {
            $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => $nama, 'location_program' => $this->d['lokasiA']->id]);
            Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
        }
        // Mahasiswa biasa (bukan ketua) tidak dihitung
        Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $this->d['lokasiA']->id]);

        $filter = ['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa];
        $publik = $this->publik($filter)['kelompok']->keyBy('nama_pt');
        $dashboard = app(KpiSampahService::class)->drilldown($filter + ['bulan' => null])['kelompok']->keyBy('nama_pt');

        $this->assertSame(2, $publik['Univ Hijau']->jumlah_ketua);
        $this->assertSame(['Ketua Dua', 'Ketua Univ Hijau'], $publik['Univ Hijau']->ketua->all());
        $this->assertSame($dashboard['Univ Hijau']->jumlah_ketua, $publik['Univ Hijau']->jumlah_ketua);
    }

    public function test_bulan_outside_available_list_is_rejected(): void
    {
        $this->getJson('login/laporan?bulan='.$this->bulan)->assertOk();
        $this->getJson('login/laporan?bulan=1999-01')->assertUnprocessable()->assertJsonValidationErrors('bulan');
        $this->getJson('login/laporan?bulan=9999-12')->assertUnprocessable()->assertJsonValidationErrors('bulan');
    }

    public function test_cached_data_and_html_contain_no_email(): void
    {
        $email = $this->d['ketua_hijau']->email;
        Kpicapaian::factory()->create(['email' => $email, 'permasalahan' => 'Masalah Uji', 'bulan' => $this->bulan.'-01']);
        Cache::flush();

        $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Masalah Uji')->assertDontSee($email);

        $data = $this->publik(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa]);
        $this->assertStringNotContainsString($email, serialize($data));
        $this->assertStringNotContainsString('nama_ketua', serialize($data['kelompok']));
    }

    public function test_invalid_klaster_is_rejected(): void
    {
        $this->getJson('login/laporan?klaster=ungu')->assertUnprocessable()->assertJsonValidationErrors('klaster');
        $this->getJson('login/laporan?klaster[]=hijau')->assertUnprocessable()->assertJsonValidationErrors('klaster');
        $this->getJson('login/laporan?klaster=')->assertOk();
    }

    public function test_png_button_only_on_default_and_reset_otherwise(): void
    {
        $this->get('login')->assertOk()
            ->assertSee('data-png-download', false)->assertDontSee('data-drill-reset', false)
            ->assertSee('Program GRADASI : KKN Tematik')->assertSee('Capaian Program')->assertSee('Dokumen');
        $this->get('login/laporan?bulan='.$this->bulan)->assertOk()->assertSee('data-png-download', false);
        $this->get('login/laporan?klaster=kuning')->assertOk()->assertSee('data-drill-reset', false)->assertDontSee('data-png-download', false);
        $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan)->assertOk()->assertSee('data-drill-reset', false);
    }

    public function test_dashboard_keeps_inclusive_threshold_and_old_partial(): void
    {
        // Kec Alfa tepat 20,00%: hijau di dashboard (>=), kuning di publik (>)
        $this->get('login/laporan')->assertOk()->assertSee('table-warning')->assertDontSee('table-success');
        $this->loginAs('admin');
        $this->get('dashboardkpi', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('laporan._drilldown')
            ->assertSee('table-success')->assertDontSee('data-png-download', false)->assertDontSee('name="klaster"', false);
    }
}
