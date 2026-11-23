<?php

namespace Tests\Feature;

use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class CapaianKpiKelurahanTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private string $bulan;

    private array $d = [];

    /**
     * Koef lokasi = 1, tiap log jiwa 100 -> penurunan = 100 - residu.
     * Kota Alfa / Kec Alfa / Desa Satu (baseline 10%): Univ Hijau 25% (2 DPL) + Univ Tanpa Data (ketua saja).
     * Kabupaten Beta / Kec Beta / Desa Dua: Univ Kuning 15% (bulan lalu) + 35% (2 bulan lalu) -> semua bulan 25%.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->bulan = now()->subMonth()->format('Y-m');
        $lokasiA = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Alfa', 'koefisien_timbulan_sampah' => 1]);
        $lokasiB = LokasiProgram::factory()->create(['nama_lokasi' => 'Kabupaten Beta', 'koefisien_timbulan_sampah' => 1]);
        $kecA = Kecamatan::factory()->create(['kecamatan' => 'Kec Alfa']);
        $kecB = Kecamatan::factory()->create(['kecamatan' => 'Kec Beta']);
        $desaSatu = Desa::factory()->create(['id_kecamatan' => $kecA->id_kecamatan, 'desa' => 'Desa Satu', 'baseline' => 10, 'satuan_baseline' => '%']);
        $desaDua = Desa::factory()->create(['id_kecamatan' => $kecB->id_kecamatan, 'desa' => 'Desa Dua']);

        [$hijau, $this->d['ketua_hijau']] = $this->ketua('Univ Hijau', $lokasiA, $desaSatu, 25);
        [$this->d['pt_kosong']] = $this->ketua('Univ Tanpa Data', $lokasiA, $desaSatu, null);
        $this->d += ['pt_hijau' => $hijau, 'desa_satu' => $desaSatu];
        [, $ketuaKuning] = $this->ketua('Univ Kuning', $lokasiB, $desaDua, 15);
        $this->logSampah($ketuaKuning, now()->subMonths(2)->format('Y-m'), 35);
        Dpl::factory()->count(2)->create(['kodept' => $hijau->npsn, 'location_program' => $lokasiA->id]);
    }

    /** @return array{0: Satuanpendidikan, 1: Mahasiswa} */
    private function ketua(string $namaPt, LokasiProgram $lokasi, Desa $desa, ?float $persen): array
    {
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => $namaPt]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email]);
        if ($persen !== null) {
            $this->logSampah($mhs, $this->bulan, $persen);
        }

        return [$pt, $mhs];
    }

    private function logSampah(Mahasiswa $mhs, string $bulan, float $persen): void
    {
        PendataanPemilahanSampah::factory()->create([
            'email' => $mhs->email, 'tanggal' => $bulan.'-10', 'memilah' => true, 'jumlah_anggota_keluarga' => 100,
            'organik_kg' => 0, 'anorganik_kg' => 0, 'residu_kg' => 100 - $persen,
        ]);
    }

    private function urutanDesa(array $query): array
    {
        $this->loginAs('admin');

        return $this->get(route('capaian-kpi', $query))->assertOk()->viewData('kelurahan')->pluck('desa')->all();
    }

    public function test_kelurahan_and_pt_rows_have_kpi_values_sorted_by_capaian(): void
    {
        $data = app(PenguranganSampahService::class)->capaianKpiKelurahan(null);

        $this->assertSame($this->bulan, $data['bulan']);
        $this->assertSame(['Desa Satu', 'Desa Dua'], $data['kelurahan']->pluck('desa')->all());

        $satu = $data['kelurahan']->first();
        $this->assertSame(['Kota Alfa', 'Kec Alfa'], [$satu->kab_kota, $satu->kecamatan]);
        $this->assertEquals(10.0, $satu->baseline);
        $this->assertEquals(100.0, $satu->persen_ketaatan);
        $this->assertEquals(25.0, $satu->persen);
        $this->assertSame('hijau', $satu->klaster);

        $this->assertSame(['Univ Hijau', 'Univ Tanpa Data'], $satu->pt->pluck('nama_pt')->all());
        [$ptHijau, $ptKosong] = $satu->pt->all();
        $this->assertSame([1, 2, 25.0, 'hijau'], [$ptHijau->jumlah_mahasiswa, $ptHijau->jumlah_dpl, (float) $ptHijau->persen, $ptHijau->klaster]);
        $this->assertSame([1, 0, null, null], [$ptKosong->jumlah_mahasiswa, $ptKosong->jumlah_dpl, $ptKosong->persen, $ptKosong->klaster]);

        $dua = $data['kelurahan']->last();
        $this->assertEquals(15.0, $dua->persen);
        $this->assertSame('kuning', $dua->klaster);
        $this->assertSame(['Univ Kuning'], $dua->pt->pluck('nama_pt')->all());
    }

    public function test_admin_kepala_pemda_can_open_page(): void
    {
        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->get(route('capaian-kpi'))
                ->assertOk()
                ->assertSeeInOrder(['Desa Satu', 'Univ Hijau', 'Univ Tanpa Data', 'Desa Dua', 'Univ Kuning'])
                ->assertSee('Capaian KPI Kelurahan/Desa');
        }
    }

    public function test_other_roles_are_redirected(): void
    {
        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $this->get(route('capaian-kpi'))->assertRedirect(route('home'));
        }
    }

    public function test_semua_bulan_aggregates_every_month(): void
    {
        $data = app(PenguranganSampahService::class)->capaianKpiKelurahan(PenguranganSampahService::SEMUA_BULAN);

        $this->assertSame(PenguranganSampahService::SEMUA_BULAN, $data['bulan']);
        $dua = $data['kelurahan']->firstWhere('desa', 'Desa Dua');
        $this->assertEquals(25.0, $dua->persen);
        $this->assertEquals(25.0, $dua->pt->first()->persen);
        $this->assertSame('hijau', $dua->klaster);
    }

    public function test_sort_by_name_and_klaster_applies_server_side(): void
    {
        $this->assertSame(['Desa Satu', 'Desa Dua'], $this->urutanDesa([]));
        $this->assertSame(['Desa Dua', 'Desa Satu'], $this->urutanDesa(['sort' => 'kab_kota']));
        $this->assertSame(['Desa Satu', 'Desa Dua'], $this->urutanDesa(['sort' => 'kab_kota', 'dir' => 'desc']));
        $this->assertSame(['Desa Dua', 'Desa Satu'], $this->urutanDesa(['sort' => 'desa']));
        $this->assertSame(['Desa Satu', 'Desa Dua'], $this->urutanDesa(['sort' => 'kecamatan']));
        $this->assertSame(['Desa Satu', 'Desa Dua'], $this->urutanDesa(['sort' => 'klaster']));
        $this->assertSame(['Desa Dua', 'Desa Satu'], $this->urutanDesa(['sort' => 'klaster', 'dir' => 'desc']));
    }

    public function test_search_matches_wilayah_and_pt_name_keeping_whole_kelurahan(): void
    {
        $this->assertSame(['Desa Dua'], $this->urutanDesa(['q' => 'beta']));
        $this->assertSame(['Desa Satu'], $this->urutanDesa(['q' => 'tanpa data']));
        $this->assertSame([], $this->urutanDesa(['q' => 'tidak ada']));

        $kel = $this->get(route('capaian-kpi', ['q' => 'tanpa data']))->viewData('kelurahan')->first();
        $this->assertSame(['Univ Hijau', 'Univ Tanpa Data'], $kel->pt->pluck('nama_pt')->all());
    }

    public function test_export_uses_standard_layout_with_one_row_per_pts(): void
    {
        $this->loginAs('pemda');
        $ws = $this->xlsxFromResponse(
            $this->get(route('capaian-kpi.export', ['q' => 'alfa', 'sort' => 'desa'])),
            '/capaian-kpi-kelurahan-'.$this->bulan.'_.*\\.xlsx/'
        );

        $this->assertSame('Capaian KPI Kelurahan/Desa', $this->cellText($ws, 'A1'));
        $this->assertStringContainsString('Pencarian: alfa', $this->cellText($ws, 'A2'));
        $this->assertSame([
            'No.', 'Kabupaten/Kota', 'Kecamatan', 'Kelurahan/Desa', 'Baseline', 'Ketaatan Pemilahan (%)',
            'Penurunan Timbulan Sampah Kelurahan (%)', 'Klaster Kelurahan', 'PTS', 'Mahasiswa', 'DPL',
            'Penurunan Timbulan Sampah PTS (%)', 'Klaster PTS',
        ], $this->headerRow($ws));

        // 1 kelurahan, 2 PTS -> 2 baris, data kelurahan diulang
        $rows = $this->dataRows($ws);
        $this->assertCount(2, $rows);
        $this->assertSame(['1', 'Kota Alfa', 'Kec Alfa', 'Desa Satu', '10,00%', '100.00%', '25.00%', 'Hijau', 'Univ Hijau', '1', '2', '25.00%', 'Hijau'], array_map('strval', $rows[0]));
        $this->assertSame(['Desa Satu', 'Univ Tanpa Data', '1', '0', '-', '-'], array_map('strval', [$rows[1][3], ...array_slice($rows[1], 8)]));
    }

    public function test_detail_shows_capaian_of_bulan_and_ketua_without_isian(): void
    {
        CapaianKegiatan::factory()->create(['email' => $this->d['ketua_hijau']->email, 'bulan' => $this->bulan.'-01', 'permasalahan' => 'Masalah Bulan Ini']);
        CapaianKegiatan::factory()->create(['email' => $this->d['ketua_hijau']->email, 'bulan' => now()->subMonths(3)->format('Y-m').'-01', 'permasalahan' => 'Masalah Lama']);
        $this->d['ketua_hijau']->update(['phone' => '081234567890']);
        $this->loginAs('kepala');
        $param = ['desa' => $this->d['desa_satu']->id_desa, 'kodept' => $this->d['pt_hijau']->npsn];

        $this->get(route('capaian-kpi.detail', $param + ['bulan' => $this->bulan]))->assertOk()
            ->assertSee('Masalah Bulan Ini')->assertDontSee('Masalah Lama')
            ->assertSee('081234567890')->assertDontSee('<th class="text-center">Bulan</th>', false);
        $this->get(route('capaian-kpi.detail', $param + ['bulan' => PenguranganSampahService::SEMUA_BULAN]))->assertOk()
            ->assertSeeInOrder(['Masalah Bulan Ini', 'Masalah Lama'])->assertSee('<th class="text-center">Bulan</th>', false);
        $this->get(route('capaian-kpi.detail', ['kodept' => $this->d['pt_kosong']->npsn] + $param + ['bulan' => $this->bulan]))->assertOk()
            ->assertSee('Belum mengisi capaian');
    }

    public function test_detail_validates_params_and_roles(): void
    {
        $this->loginAs('admin');
        $this->get(route('capaian-kpi.detail', ['desa' => 'tidak-ada', 'kodept' => $this->d['pt_hijau']->npsn]))->assertSessionHasErrors('desa');
        $this->get(route('capaian-kpi.detail', ['desa' => $this->d['desa_satu']->id_desa, 'kodept' => '999']))->assertSessionHasErrors('kodept');
        $this->get(route('capaian-kpi.detail', ['desa' => $this->d['desa_satu']->id_desa, 'kodept' => $this->d['pt_hijau']->npsn, 'bulan' => 'x']))->assertSessionHasErrors('bulan');

        $this->loginAs('pt');
        $this->get(route('capaian-kpi.detail', ['desa' => $this->d['desa_satu']->id_desa, 'kodept' => $this->d['pt_hijau']->npsn]))->assertRedirect(route('home'));
    }

    public function test_pts_rows_have_detail_action_button(): void
    {
        $this->loginAs('admin');
        $this->get(route('capaian-kpi'))->assertOk()
            ->assertSee('btn-action-view', false)
            ->assertSee(e(route('capaian-kpi.detail', ['desa' => $this->d['desa_satu']->id_desa, 'kodept' => $this->d['pt_hijau']->npsn, 'bulan' => $this->bulan])), false);
    }

    public function test_ajax_request_returns_table_partial_with_filter_links(): void
    {
        $this->loginAs('admin');
        $this->get(route('capaian-kpi', ['q' => 'beta', 'sort' => 'desa']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertViewIs('capaiankpi._tabel')
            ->assertDontSee('data-filter-host', false)
            ->assertSee('data-filter-links', false)
            ->assertSee('Desa Dua')
            ->assertDontSee('Desa Satu')
            ->assertSee(e(route('capaian-kpi.export', ['bulan' => $this->bulan, 'q' => 'beta', 'sort' => 'desa', 'dir' => 'asc'])), false);

        $this->get(route('capaian-kpi'))->assertViewIs('capaiankpi.index')->assertSee('data-filter-host', false);
    }

    public function test_export_is_forbidden_for_pt(): void
    {
        $this->loginAs('pt');
        $this->get(route('capaian-kpi.export'))->assertRedirect(route('home'));
    }

    public function test_invalid_params_are_rejected(): void
    {
        $this->loginAs('admin');
        $this->get(route('capaian-kpi', ['bulan' => '2026-13']))->assertSessionHasErrors('bulan');
        $this->get(route('capaian-kpi', ['sort' => 'email']))->assertSessionHasErrors('sort');
        $this->get(route('capaian-kpi', ['dir' => 'up']))->assertSessionHasErrors('dir');
        $this->get(route('capaian-kpi', ['q' => str_repeat('a', 101)]))->assertSessionHasErrors('q');
    }
}
