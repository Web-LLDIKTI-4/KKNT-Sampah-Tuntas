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

        $this->put('kpisampah/insert', $this->payload())->assertJsonPath('errors.bulan.0', 'Anda sudah mengisi data sampah untuk bulan tersebut!');
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
     * pengurangan 25 kg (25% -> terpenuhi) dan 10 kg (10% -> belum terpenuhi).
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

    public function test_kpi_target_met_at_or_above_twenty_percent_with_proportional_capaian(): void
    {
        $this->assertFalse(Kpisampah::terpenuhi(0.0));
        $this->assertFalse(Kpisampah::terpenuhi(19.99));
        $this->assertTrue(Kpisampah::terpenuhi(20.0));
        $this->assertTrue(Kpisampah::terpenuhi(85.0));
        $this->assertNull(Kpisampah::terpenuhi(null));

        $this->assertEquals(0, Kpisampah::capaian(0.0));
        $this->assertEquals(50, Kpisampah::capaian(10));
        $this->assertEquals(99.95, Kpisampah::capaian(19.99));
        $this->assertEquals(100, Kpisampah::capaian(20));
        $this->assertEquals(100, Kpisampah::capaian(40));
        $this->assertNull(Kpisampah::capaian(null));
    }

    public function test_qc_timbulan_zero_and_persen_above_hundred(): void
    {
        $this->assertNull(Kpisampah::persen(5, 0));
        $this->assertNull(Kpisampah::klaster(Kpisampah::persen(0, 0)));
        $this->assertEquals(100, Kpisampah::capaian(150.0));
        $this->assertSame('hijau', Kpisampah::klaster(150.0));
        $this->assertSame('', Kpisampah::warnaSel(null));
        $this->assertSame('table-warning', Kpisampah::warnaSel(10.0));
    }

    public function test_drilldown_shows_kecamatan_kelurahan_and_kelompok_with_detail(): void
    {
        $bulan = now()->subMonth()->format('Y-m');
        $data = $this->duaPt($bulan);
        $kpi = Kpi::factory()->create(['nama_kpi' => 'Bank Sampah']);
        Kpicapaian::factory()->create(['email' => $data[1]['ketua']->email, 'id_kpi' => $kpi->id_kpi, 'permasalahan' => 'Warga <b>belum</b> memilah', 'status_capaian' => 'P', 'bulan' => $bulan.'-01']);

        $laporan = app(KpiSampahService::class)->drilldown([
            'id_kecamatan' => $data['kecamatan']->id_kecamatan,
            'id_desa' => $data[1]['desa']->id_desa,
        ]);
        $this->assertSame($bulan, $laporan['params']['bulan']);
        $this->assertEquals(17.5, $laporan['kecamatan']->first()->kecamatan->first()->persen);
        $this->assertEquals([25, 10], $laporan['kelurahan']->sortBy('desa')->pluck('persen')->sort()->reverse()->values()->all());
        $this->assertSame(['Univ Rendah'], $laporan['kelompok']->pluck('nama_pt')->all());
        $this->assertCount(1, $laporan['kelompok']->first()->capaian);
        $this->assertSame(1, $laporan['kelompok']->first()->jumlah_ketua);

        $this->loginAs('admin');
        $this->get('dashboardkpi?kecamatan='.$data['kecamatan']->id_kecamatan.'&desa='.$data[1]['desa']->id_desa, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('laporan._capaian_publik')
            ->assertViewHas('kelompok', fn ($k) => $k->first()->jumlah_ketua === 1)
            ->assertSee('Kec Uji')->assertSee('10,00%')->assertDontSee('Capaian 50,00%')
            ->assertSee('Warga &lt;b&gt;belum&lt;/b&gt; memilah', false)->assertSee('Proses')
            ->assertSee('<span class="badge bg-success">Hijau</span></td>', false)->assertSee('<span class="badge bg-warning">Kuning</span></td>', false);
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

        // Kolom A-Z: 16 kolom kelurahan + 10 kolom total bulan
        $this->assertCount(26, $rows[0]);
        $this->assertSame(['Bulan', 'Persentase Pengurangan Sampah (%)'], [$rows[0][0], $rows[0][25]]);
        $this->assertCount(3, $rows);
        $this->assertSame(['Univ Lebih', 'Univ Rendah'], [$rows[1][2], $rows[2][2]]);
        // Total bulan hanya di baris pertama: timbulan 200, pengurangan 35 => 17,5%
        $this->assertEquals([200.0, 35.0, 0.175], [$rows[1][19], $rows[1][22], $rows[1][25]]);
        $this->assertNull($rows[2][19]);
        $this->assertSame(0, $rows[1][15]);
    }

    public function test_export_total_columns_are_per_bulan_merged_over_all_kecamatan(): void
    {
        $bulan = now()->subMonth()->format('Y-m');
        $dua = $this->duaPt($bulan); // Kec Uji: timbulan 200, pengurangan 35
        $kecLain = Kecamatan::factory()->create(['kecamatan' => 'Kec Zeta']);
        $desaLain = Desa::factory()->create(['id_kecamatan' => $kecLain->id_kecamatan]);
        $mhs = Mahasiswa::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        $this->isi($mhs->email, $desaLain->id_desa, $bulan, []); // timbulan 100, pengurangan 20

        $sampah = app(KpiSampahService::class);
        $sheet = new DataSampahSheet($sampah->detail([]), $sampah->rekapKecamatan([]));
        $rows = $sheet->array();
        $prop = fn (string $nama) => (new \ReflectionProperty($sheet, $nama))->getValue($sheet);

        // 1 nilai per bulan: 300 timbulan, 55 pengurangan -> 18,33% (rumah 300, memilah 150 -> 50%)
        $this->assertCount(4, $rows);
        $this->assertEquals([300, 150, 0.5, 300.0, 55.0, 0.1833], [$rows[1][16], $rows[1][17], $rows[1][18], $rows[1][19], $rows[1][22], $rows[1][25]]);
        $this->assertNull($rows[2][19]);
        $this->assertNull($rows[3][19]);
        // Q-Z & A di-merge per bulan (baris 2-4), B tetap per kecamatan (Kec Uji baris 2-3)
        $merges = $prop('merges');
        foreach (['A', 'Q', 'U', 'Z'] as $kolom) {
            $this->assertContains("{$kolom}2:{$kolom}4", $merges);
        }
        $this->assertContains('B2:B3', $merges);
        $this->assertNotContains('Q2:Q3', $merges);
        $this->assertSame(Kpisampah::KLASTER['kuning']['rgb'], $prop('warna')['Z2']);

        // Mengikuti filter export: hanya PT Univ Lebih (timbulan 100, pengurangan 25)
        $filter = ['kodept' => $dua[0]['pt']->npsn];
        $rows = (new DataSampahSheet($sampah->detail($filter), $sampah->rekapKecamatan($filter)))->array();
        $this->assertEquals([100.0, 25.0, 0.25], [$rows[1][19], $rows[1][22], $rows[1][25]]);
    }

    public function test_pt_klaster_thresholds_and_filtering(): void
    {
        $this->assertSame(['merah', 'merah', 'kuning', 'kuning', 'hijau', 'hijau', null],
            array_map(fn ($p) => Kpisampah::klaster($p), [0.0, 9.99, 10.0, 19.99, 20.0, 100.0, null]));

        $data = $this->duaPt(now()->subMonth()->format('Y-m'));
        $this->loginAs('kepala');

        $this->get('rekapsampah')->assertOk()
            ->assertViewHas('klasterPt', fn ($k) => $k[$data[0]['pt']->npsn]->klaster === 'hijau' && $k[$data[1]['pt']->npsn]->klaster === 'kuning');
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

        $this->get('login')->assertOk()->assertSee('Persentase Pengurangan Sampah (%)')->assertSee('Kec Uji')->assertDontSee('Univ Rendah');
        $this->get('login/laporan?kecamatan='.$data['kecamatan']->id_kecamatan)->assertOk()
            ->assertSee('Sebaran Lokasi (Kelurahan)')->assertSee($data[0]['desa']->desa)->assertDontSee('Univ Rendah');
        $this->get('login/laporan?kecamatan='.$data['kecamatan']->id_kecamatan.'&desa='.$data[1]['desa']->id_desa)->assertOk()
            ->assertSee('Univ Rendah')->assertDontSee('Univ Lebih');

        $this->getJson('login/laporan?kecamatan=bukan-uuid')->assertUnprocessable();
        $this->getJson('login/laporan?kecamatan='.$kecamatanLain->id_kecamatan.'&desa='.$data[1]['desa']->id_desa)->assertUnprocessable();
    }

    private function ketuaDiDesa(string $idDesa): User
    {
        $user = $this->loginKetua();
        Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->update(['id_desa' => $idDesa]);

        return $user;
    }

    public function test_rekap_groups_per_pt_for_admin_and_per_ketua_for_pt(): void
    {
        $desa = Desa::factory()->create();
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Univ Tiga Ketua']);
        $ptLain = Satuanpendidikan::factory()->create(['nm_lemb' => 'Univ Lain']);
        $bulan = now()->subMonth()->format('Y-m');
        // 3 ketua 1 PT: timbulan 100+200+300 = 600, pengurangan 20+40+60 = 120 -> 20%; rumah 300, memilah 150 -> 50%
        foreach ([1, 2, 3] as $n) {
            $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => 'Ketua Rahasia '.$n]);
            $this->isi($mhs->email, $desa->id_desa, $bulan, [
                'timbulan' => 100 * $n, 'pengurangan_organik' => 10 * $n, 'pengurangan_anorganik' => 10 * $n, 'residu' => 80 * $n,
            ]);
        }
        $this->isi(Mahasiswa::factory()->create(['kodept' => $ptLain->npsn])->email, $desa->id_desa, $bulan, []);

        $rows = app(KpiSampahService::class)->detail([])->where('id_desa', $desa->id_desa)->keyBy('nama_pt');
        $this->assertCount(2, $rows);
        $r = $rows['Univ Tiga Ketua'];
        $this->assertEquals([3, 3, 300, 150, 600.0, 60.0, 60.0, 120.0, 480.0, 0], [
            (int) $r->jml_rw_kbs, (int) $r->jml_rw_non_kbs, (int) $r->jml_rumah, (int) $r->jml_rumah_memilah, (float) $r->timbulan,
            (float) $r->pengurangan_organik, (float) $r->pengurangan_anorganik, (float) $r->pengurangan, (float) $r->residu, (int) $r->jml_bank_sampah,
        ]);
        $this->assertEquals(20.0, $r->persen_pengurangan);
        $this->assertEquals(50.0, $r->persen_ketaatan);

        // Total kecamatan tetap dari semua isian (700 timbulan)
        $total = app(KpiSampahService::class)->rekapKecamatan(['bulan' => $bulan])->firstWhere('id_kecamatan', $desa->id_kecamatan);
        $this->assertEquals(700.0, (float) $total->total_timbulan);

        $this->loginAs('admin');
        $this->get('rekapsampah?bulan='.$bulan)->assertOk()
            ->assertViewHas('perBulan', fn ($p) => self::jumlahBaris($p) === 2)
            ->assertSee('Univ Tiga Ketua')->assertDontSee('Ketua Rahasia');

        // Role PT: 1 baris per ketua + nama ketua; total kecamatan tetap dari PT tsb (600)
        $this->loginAs('pt', ['email' => $pt->npsn]);
        $this->get('rekapsampah?bulan='.$bulan)->assertOk()
            ->assertViewHas('perBulan', fn ($p) => self::jumlahBaris($p) === 3
                && (float) $p->first()->kecamatan->first()->total->total_timbulan === 600.0)
            ->assertSee('Ketua Rahasia 1')->assertSee('Ketua Rahasia 3')->assertDontSee('Univ Lain');

        // Export mode per ketua: nama ketua di kolom PT, struktur kolom sama
        $sampah = app(KpiSampahService::class);
        $filter = ['bulan' => $bulan, 'kodept' => $pt->npsn];
        $rows = (new DataSampahSheet($sampah->detail($filter, true), $sampah->rekapKecamatan($filter)))->array();
        $this->assertCount(4, $rows);
        $this->assertCount(count($rows[0]), $rows[1]);
        $this->assertStringContainsString('Univ Tiga Ketua – Ketua Rahasia', $rows[1][2]);
    }

    public function test_second_ketua_in_same_kelurahan_and_month_can_save(): void
    {
        $desa = Desa::factory()->create()->id_desa;
        $this->ketuaDiDesa($desa);
        $this->put('kpisampah/insert', $this->payload())->assertJson(['success' => true]);

        $this->ketuaDiDesa($desa);
        $this->put('kpisampah/insert', $this->payload())->assertJson(['success' => true]);

        $this->assertSame(2, Kpisampah::where('id_desa', $desa)->where('bulan', now()->subMonth()->format('Y-m-01'))->count());
    }

    public function test_same_ketua_same_month_rejected_but_other_month_and_own_edit_pass(): void
    {
        $this->loginKetua();
        $this->put('kpisampah/insert', $this->payload())->assertJson(['success' => true]);

        $this->put('kpisampah/insert', $this->payload(['timbulan' => 500]))
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.bulan.0', 'Anda sudah mengisi data sampah untuk bulan tersebut!');
        $this->put('kpisampah/insert', $this->payload(['bulan' => now()->subMonths(2)->format('Y-m')]))->assertJson(['success' => true]);

        $data = Kpisampah::where('bulan', now()->subMonth()->format('Y-m-01'))->sole();
        $this->put('kpisampah/update', $this->payload(['id_sampah' => $data->id_sampah, 'timbulan' => 800]))->assertJson(['success' => true]);
        $this->assertEquals(800, $data->fresh()->timbulan);

        // Edit tidak boleh dipindah ke bulan yang sudah terisi oleh ketua yang sama
        $this->put('kpisampah/update', $this->payload(['id_sampah' => $data->id_sampah, 'bulan' => now()->subMonths(2)->format('Y-m')]))
            ->assertJsonPath('errors.bulan.0', 'Anda sudah mengisi data sampah untuk bulan tersebut!');

        $this->assertSame(2, Kpisampah::count());
    }

    public function test_kpi_sampah_test_seeder_gives_pt_twenty_percent_and_is_rerunnable(): void
    {
        $this->seed(\Database\Seeders\KpiSampahTestSeeder::class);
        $this->seed(\Database\Seeders\KpiSampahTestSeeder::class);

        $total = app(KpiSampahService::class)->totalPer('kodept', ['bulan' => now()->format('Y-m')])['049901'];
        $this->assertEquals(20.0, $total->persen_pengurangan);
        $this->assertEquals(40.0, $total->persen_ketaatan);
        $this->assertSame(3, Kpisampah::where('email', 'like', '%@sampahtest.test')->count());
        $this->assertSame(1, Kpisampah::where('email', 'like', '%@sampahtest.test')->distinct()->count('id_desa'));
    }

    public function test_unique_race_on_insert_returns_duplicate_json_not_500(): void
    {
        $this->loginKetua();
        // Simulasi race: baris (email, bulan) yang sama masuk tepat setelah validasi lolos
        $sekali = false;
        Kpisampah::creating(function (Kpisampah $model) use (&$sekali) {
            if ($sekali) {
                return;
            }
            $sekali = true;
            Kpisampah::withoutEvents(fn () => Kpisampah::create(collect($model->getAttributes())->except($model->getKeyName())->all()));
        });

        $this->put('kpisampah/insert', $this->payload())
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.bulan.0', 'Anda sudah mengisi data sampah untuk bulan tersebut!');

        $this->assertSame(1, Kpisampah::count());
    }
}
