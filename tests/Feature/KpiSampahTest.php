<?php

namespace Tests\Feature;

use App\Exports\Sheets\DataSampahSheet;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\KpiSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class KpiSampahTest extends TestCase
{
    use RefreshDatabase;

    private function loginKetua(?string $kodept = null): User
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        if ($kodept) {
            $user->mahasiswa->update(['kodept' => $kodept]);
        }
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'bulan' => now()->subMonth()->format('Y-m'),
            'jml_rw_kbs' => 2,
            'jml_rw_non_kbs' => 3,
            'jml_rumah' => 200,
            'jml_rumah_memilah' => 50,
            'timbulan' => 1000,
            'pengurangan_organik' => 300,
            'pengurangan_anorganik' => 100,
            'residu' => 600,
            'jml_bank_sampah' => 1,
        ];
    }

    private static function jumlahBaris($perBulan): int
    {
        return $perBulan->sum(fn ($b) => $b->kecamatan->sum(fn ($k) => $k->rows->count()));
    }

    private function isi(string $email, string $idDesa, string $bulan, array $nilai): Kpisampah
    {
        return Kpisampah::create(KpiSampahService::hitung($nilai + [
            'email' => $email,
            'id_desa' => $idDesa,
            'bulan' => $bulan.'-01',
            'jml_rw_kbs' => 1,
            'jml_rw_non_kbs' => 1,
            'jml_rumah' => 100,
            'jml_rumah_memilah' => 50,
            'timbulan' => 100,
            'pengurangan_organik' => 10,
            'pengurangan_anorganik' => 10,
            'residu' => 80,
            'jml_bank_sampah' => 0,
        ]));
    }

    public function test_ketua_saves_with_server_computed_values_on_chosen_kelurahan(): void
    {
        $user = $this->loginKetua();
        $desaPilihan = Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->value('id_desa');

        $this->put('kpisampah/insert', $this->payload(['pengurangan' => 999, 'persen_pengurangan' => 99]))
            ->assertJson(['success' => true]);

        $data = Kpisampah::sole();
        $this->assertSame($desaPilihan, $data->id_desa);
        $this->assertSame(now()->subMonth()->format('Y-m-01'), $data->bulan->toDateString());
        $this->assertEquals(400, $data->pengurangan);
        $this->assertEquals(25, $data->persen_ketaatan);
        $this->assertEquals(40, $data->persen_pengurangan);
    }

    public function test_invalid_input_and_duplicate_month_are_rejected(): void
    {
        $this->loginKetua();
        $this->put('kpisampah/insert', $this->payload())->assertJson(['success' => true]);

        $this->put('kpisampah/insert', $this->payload())->assertJsonPath('errors.bulan.0', 'Data kelurahan ini untuk bulan tersebut sudah ada!');
        $this->put('kpisampah/insert', $this->payload(['bulan' => now()->addMonth()->format('Y-m')]))->assertJsonPath('success', false);
        $this->put('kpisampah/insert', $this->payload(['bulan' => '2026-13']))->assertJsonPath('success', false);
        $this->put('kpisampah/insert', $this->payload(['bulan' => now()->subMonths(2)->format('Y-m'), 'jml_rumah_memilah' => 201]))
            ->assertJsonStructure(['errors' => ['jml_rumah_memilah']]);
        $this->put('kpisampah/insert', $this->payload(['bulan' => now()->subMonths(2)->format('Y-m'), 'pengurangan_organik' => 950]))
            ->assertJsonStructure(['errors' => ['pengurangan_anorganik']]);
        $this->put('kpisampah/insert', $this->payload(['bulan' => now()->subMonths(2)->format('Y-m'), 'timbulan' => -1]))
            ->assertJsonStructure(['errors' => ['timbulan']]);

        $this->assertSame(1, Kpisampah::count());
    }

    public function test_non_ketua_cannot_save(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('kpisampah/insert', $this->payload())->assertJsonPath('success', false);
        $this->assertSame(0, Kpisampah::count());
    }

    public function test_ketua_cannot_touch_other_ketua_data(): void
    {
        $other = $this->loginKetua();
        $this->put('kpisampah/insert', $this->payload())->assertJson(['success' => true]);
        $data = Kpisampah::sole();

        $this->loginKetua();
        $this->get('kpisampah/edit/'.$data->id_sampah)->assertNotFound();
        $this->put('kpisampah/update', $this->payload(['id_sampah' => $data->id_sampah]))->assertJsonPath('success', false);
        $this->put('kpisampah/destroy', ['id_sampah' => $data->id_sampah])->assertNotFound();

        $this->assertSame($other->email, $data->fresh()->email);
    }

    public function test_rekap_sums_kelurahan_per_kecamatan_and_pt_sees_only_its_data(): void
    {
        $pt1 = Satuanpendidikan::factory()->create();
        $pt2 = Satuanpendidikan::factory()->create();
        $kecamatan = Kecamatan::factory()->create(['kecamatan' => 'Kec Uji']);
        $bulan = now()->subMonth()->format('Y-m');

        foreach ([[$pt1, 100, 20], [$pt1, 300, 60], [$pt2, 600, 0]] as [$pt, $timbulan, $organik]) {
            $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn]);
            $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan]);
            $this->isi($mhs->email, $desa->id_desa, $bulan, ['timbulan' => $timbulan, 'pengurangan_organik' => $organik, 'pengurangan_anorganik' => 0, 'jml_rumah' => 100, 'jml_rumah_memilah' => 40]);
        }

        $rekap = app(KpiSampahService::class)->rekapKecamatan(['bulan' => $bulan])->sole();
        $this->assertSame(3, (int) $rekap->jml_kelurahan);
        $this->assertEquals(1000, $rekap->total_timbulan);
        $this->assertEquals(80, $rekap->total_pengurangan);
        $this->assertEquals(8, $rekap->persen_pengurangan);
        $this->assertEquals(40, $rekap->persen_ketaatan);

        $this->loginAs('pt', ['email' => $pt1->npsn]);
        $this->get('rekapsampah?kodept='.$pt2->npsn)->assertOk()
            ->assertViewHas('total', fn ($t) => (float) $t->total_timbulan === 400.0 && $t->persen_pengurangan === 20.0)
            ->assertViewHas('perBulan', fn ($p) => self::jumlahBaris($p) === 2);

        $this->loginAs('admin');
        $this->get('rekapsampah')->assertOk()->assertViewHas('perBulan', fn ($p) => self::jumlahBaris($p) === 3
            && $p->first()->kecamatan->sole()->total->persen_pengurangan === 8.0)
            ->assertSee('Total Kecamatan (3 kelurahan)');
        $this->get('rekapsampah?kodept='.$pt1->npsn.'&bulan=semua', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('rekapsampah._tabel')->assertViewHas('filter', fn ($f) => $f['bulan'] === null);
        $this->get('rekapsampah?bulan=2026-13')->assertSessionHasErrors('bulan');

        $this->loginAs('mahasiswa');
        $this->get('rekapsampah')->assertRedirect();
    }

    /**
     * Dua PT di kecamatan yang sama, kelurahan berbeda: timbulan 100 kg masing-masing,
     * pengurangan 25 kg (25% -> belum terpenuhi) dan 10 kg (10% -> terpenuhi).
     */
    private function duaPt(string $bulan): array
    {
        $kecamatan = Kecamatan::factory()->create(['kecamatan' => 'Kec Uji']);
        $hasil = ['kecamatan' => $kecamatan];
        foreach ([[25, 'Univ Lebih'], [10, 'Univ Rendah']] as [$organik, $nama]) {
            $pt = Satuanpendidikan::factory()->create(['nm_lemb' => $nama]);
            $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => 'Ketua '.$nama]);
            $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan]);
            Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
            $this->isi($mhs->email, $desa->id_desa, $bulan, ['pengurangan_organik' => $organik, 'pengurangan_anorganik' => 0, 'jml_bank_sampah' => 0]);
            $hasil[] = ['pt' => $pt, 'desa' => $desa, 'ketua' => $mhs];
        }

        return $hasil;
    }

    public function test_kpi_target_met_at_or_below_twenty_percent_with_proportional_capaian(): void
    {
        $this->assertTrue(Kpisampah::terpenuhi(0.0));
        $this->assertTrue(Kpisampah::terpenuhi(20.0));
        $this->assertFalse(Kpisampah::terpenuhi(20.01));
        $this->assertNull(Kpisampah::terpenuhi(null));

        $this->assertEquals(100, Kpisampah::capaian(12.5));
        $this->assertEquals(50, Kpisampah::capaian(40));
        $this->assertNull(Kpisampah::capaian(null));
    }

    public function test_drilldown_shows_kecamatan_kelurahan_and_kelompok_with_detail(): void
    {
        $bulan = now()->subMonth()->format('Y-m');
        $data = $this->duaPt($bulan);
        $kpi = Kpi::factory()->create(['nama_kpi' => 'Bank Sampah']);
        Kpicapaian::factory()->create(['email' => $data[1]['ketua']->email, 'id_kpi' => $kpi->id_kpi, 'permasalahan' => 'Warga <b>belum</b> memilah', 'status_capaian' => 'P']);

        $laporan = app(KpiSampahService::class)->drilldown([
            'id_kecamatan' => $data['kecamatan']->id_kecamatan,
            'id_desa' => $data[1]['desa']->id_desa,
        ]);
        $this->assertSame($bulan, $laporan['params']['bulan']);
        $this->assertEquals(17.5, $laporan['kecamatan']->first()->kecamatan->first()->persen);
        $this->assertEquals([25, 10], $laporan['kelurahan']->sortBy('desa')->pluck('persen')->sort()->reverse()->values()->all());
        $this->assertSame(['Univ Rendah'], $laporan['kelompok']->pluck('nama_pt')->all());
        $this->assertCount(1, $laporan['kelompok']->first()->capaian);

        $this->loginAs('admin');
        $this->get('dashboardkpi?kecamatan='.$data['kecamatan']->id_kecamatan.'&desa='.$data[1]['desa']->id_desa, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('laporan._drilldown')
            ->assertSee('Kec Uji')->assertSee('Ketua Univ Rendah')->assertSee('Capaian 100,00%')
            ->assertSee('Warga &lt;b&gt;belum&lt;/b&gt; memilah', false)->assertSee('Proses')
            ->assertSee('table-success')->assertSee('table-warning');
    }

    public function test_pt_dashboard_only_shows_its_own_kelompok(): void
    {
        $data = $this->duaPt(now()->subMonth()->format('Y-m'));

        $this->loginAs('pt', ['email' => $data[1]['pt']->npsn]);
        $this->get('dashboardkpi?kodept='.$data[0]['pt']->npsn.'&kecamatan='.$data['kecamatan']->id_kecamatan.'&desa='.$data[0]['desa']->id_desa)
            ->assertOk()
            ->assertViewHas('laporan', fn ($l) => $l['kelurahan']->pluck('id_desa')->all() === [$data[1]['desa']->id_desa]
                && $l['kelompok']->isEmpty()
                && $l['total']->persen_pengurangan == 10.0);
    }

    public function test_export_data_sampah_sheet_has_kelurahan_rows_and_merged_kecamatan_totals(): void
    {
        $this->duaPt(now()->subMonth()->format('Y-m'));

        $sampah = app(KpiSampahService::class);
        $rows = (new DataSampahSheet($sampah->detail([]), $sampah->rekapKecamatan([])))->array();

        // Kolom A-Z persis format rekap: 16 kolom kelurahan + 10 kolom total kecamatan
        $this->assertCount(26, $rows[0]);
        $this->assertSame(['Bulan', 'Persentase Pengurangan Sampah (%)'], [$rows[0][0], $rows[0][25]]);
        $this->assertCount(3, $rows);
        $this->assertSame(['Univ Lebih', 'Univ Rendah'], [$rows[1][2], $rows[2][2]]);
        // Total kecamatan hanya di baris pertama: timbulan 200, pengurangan 35 => 17,5%
        $this->assertEquals([200.0, 35.0, 0.175], [$rows[1][19], $rows[1][22], $rows[1][25]]);
        $this->assertNull($rows[2][19]);
        $this->assertSame(0, $rows[1][15]);
    }

    public function test_pt_klaster_thresholds_and_filtering(): void
    {
        $this->assertSame(['hijau', 'hijau', 'kuning', 'kuning', 'merah', null],
            array_map(fn ($p) => Kpisampah::klaster($p), [0.0, 20.0, 20.01, 30.0, 30.01, null]));

        $data = $this->duaPt(now()->subMonth()->format('Y-m'));
        $this->loginAs('kepala');

        $this->get('rekapsampah')->assertOk()
            ->assertViewHas('klasterPt', fn ($k) => $k[$data[0]['pt']->npsn]->klaster === 'kuning' && $k[$data[1]['pt']->npsn]->klaster === 'hijau');
        $this->get('rekapsampah?klaster=kuning', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertViewHas('perBulan', fn ($p) => self::jumlahBaris($p) === 1)
            ->assertViewHas('klasterPt', fn ($k) => $k->count() === 2);
        $this->get('rekapsampah?klaster=merah', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertViewHas('perBulan', fn ($p) => $p->isEmpty())->assertSee('Belum ada data');
        $this->get('rekapsampah?klaster=ungu')->assertSessionHasErrors('klaster');

        // Akun PT tidak melihat rekap klaster maupun parameter klaster
        $this->loginAs('pt', ['email' => $data[0]['pt']->npsn]);
        $this->get('rekapsampah?klaster=hijau')->assertOk()
            ->assertViewHas('klasterPt', fn ($k) => $k->isEmpty())
            ->assertViewHas('perBulan', fn ($p) => self::jumlahBaris($p) === 1);
    }

    public function test_login_report_drills_down_publicly_with_validation(): void
    {
        $data = $this->duaPt(now()->subMonth()->format('Y-m'));
        $kecamatanLain = Kecamatan::factory()->create();
        Cache::flush();

        $this->get('login')->assertOk()->assertSee('Sebaran Lokasi (Kecamatan)')->assertSee('Kec Uji')->assertDontSee('Univ Rendah');
        $this->get('login/laporan?kecamatan='.$data['kecamatan']->id_kecamatan)->assertOk()
            ->assertSee('Sebaran Lokasi (Kelurahan)')->assertSee($data[0]['desa']->desa)->assertDontSee('Univ Rendah');
        $this->get('login/laporan?kecamatan='.$data['kecamatan']->id_kecamatan.'&desa='.$data[1]['desa']->id_desa)->assertOk()
            ->assertSee('Univ Rendah')->assertDontSee('Univ Lebih');

        $this->getJson('login/laporan?kecamatan=bukan-uuid')->assertUnprocessable();
        $this->getJson('login/laporan?kecamatan='.$kecamatanLain->id_kecamatan.'&desa='.$data[1]['desa']->id_desa)->assertUnprocessable();
    }
}
