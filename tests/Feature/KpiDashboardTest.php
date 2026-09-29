<?php

namespace Tests\Feature;

use App\Exports\KpiRekapExport;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\KpiRekapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    private LokasiProgram $lokasi;

    private Satuanpendidikan $pt1;

    private Satuanpendidikan $pt2;

    private Kpitarget $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lokasi = LokasiProgram::factory()->create();
        $this->pt1 = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Satu']);
        $this->pt2 = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Dua']);
        $this->target = Kpitarget::factory()->create([
            'id_kpi' => Kpi::factory()->create()->id_kpi,
            'target' => 80,
            'satuan' => '%',
        ]);

        // PT1: 2 kelompok (Selesai 90/80 => 100%, belum isi => tidak dihitung), PT2: 1 kelompok (Proses => tidak dihitung)
        $this->ketua($this->pt1, 90, 'Y');
        $this->ketua($this->pt1, null);
        $this->ketua($this->pt2, 40, 'P');
    }

    private function ketua(Satuanpendidikan $pt, ?float $realisasi, string $status = 'Y'): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $this->lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        if ($realisasi !== null) {
            Kpicapaian::factory()->create([
                'email' => $mhs->email,
                'id_kpi' => $this->target->id_kpi,
                'id_target' => $this->target->id_target,
                'realisasi' => $realisasi,
                'status_capaian' => $status,
                'satuan' => '%',
            ]);
        }

        return $mhs;
    }

    public function test_capaian_counts_only_filled_and_selesai_groups(): void
    {
        $service = app(KpiRekapService::class);
        $rows = $service->rekapPerPt([])->keyBy('kodept');

        $this->assertSame(2, $rows[$this->pt1->npsn]->jumlah_kelompok);
        $this->assertSame(1, $rows[$this->pt1->npsn]->jumlah_selesai);
        $this->assertEquals(90, $rows[$this->pt1->npsn]->realisasi);
        $this->assertEquals(100, $rows[$this->pt1->npsn]->capaian);
        $this->assertNull($rows[$this->pt2->npsn]->realisasi);
        $this->assertNull($rows[$this->pt2->npsn]->capaian);

        $summary = $service->summary([]);
        $this->assertSame(2, $summary['jumlah_pt']);
        $this->assertSame(3, $summary['total_kelompok']);
        $this->assertArrayNotHasKey('rata_capaian', $summary);
        $this->assertEquals(100, $service->rekapPerKegiatan([])->first()->capaian);
    }

    public function test_capaian_is_average_realisasi_against_target_capped_at_100(): void
    {
        $this->ketua($this->pt1, 50, 'Y');

        $row = app(KpiRekapService::class)->rekapPerPt(['kodept' => $this->pt1->npsn])->first();

        // Rata-rata (90 + 50) / 2 = 70 dari target 80
        $this->assertEquals(70, $row->realisasi);
        $this->assertEquals(87.5, $row->capaian);
        $this->assertSame(2, $row->jumlah_selesai);
    }

    public function test_rekap_per_kpi_averages_only_kegiatan_with_data(): void
    {
        $kegiatan2 = Kpitarget::factory()->create(['id_kpi' => $this->target->id_kpi, 'target' => 100]);
        Kpitarget::factory()->create(['id_kpi' => $this->target->id_kpi]);
        $ketua = Mahasiswa::where('kodept', $this->pt1->npsn)->firstOrFail();
        Kpicapaian::factory()->create([
            'email' => $ketua->email, 'id_kpi' => $this->target->id_kpi, 'id_target' => $kegiatan2->id_target,
            'realisasi' => 40, 'status_capaian' => 'Y',
        ]);

        $kpi = app(KpiRekapService::class)->rekapPerKpi([])->first();

        // (100 + 40) / 2; kegiatan ketiga tanpa data tidak ikut
        $this->assertSame(3, $kpi->jumlah_kegiatan);
        $this->assertSame(2, $kpi->kegiatan_berdata);
        $this->assertEquals(70, $kpi->capaian);
    }

    public function test_lokasi_table_uses_student_placement_and_counts_unplaced(): void
    {
        $ketua = Mahasiswa::where('kodept', $this->pt1->npsn)->firstOrFail();
        Mahasiswa_lokasi::create(['tahun' => 2026, 'id_mahasiswa' => $ketua->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa]);

        $lokasiLain = LokasiProgram::factory()->create();
        $anggota = Mahasiswa::factory()->create(['kodept' => $this->pt1->npsn, 'location_program' => $lokasiLain->id]);
        Mahasiswa_lokasi::create(['tahun' => 2026, 'id_mahasiswa' => $anggota->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa]);
        Dpl::factory()->create(['kodept' => $this->pt1->npsn, 'location_program' => $this->lokasi->id]);

        $table = app(KpiRekapService::class)->lokasiTable(['kodept' => $this->pt1->npsn]);
        $rows = $table['rows']->keyBy('lokasi');

        $this->assertEquals(['mahasiswa' => 1, 'dpl' => 1, 'kelompok' => 2, 'kecamatan' => 1, 'kelurahan' => 1],
            collect($rows[$this->lokasi->nama_lokasi])->only(['mahasiswa', 'dpl', 'kelompok', 'kecamatan', 'kelurahan'])->all());
        $this->assertSame(1, $rows[$lokasiLain->nama_lokasi]['mahasiswa']);
        $this->assertSame(1, $table['belum_lokasi']);
        $this->assertEquals(['pt' => 1, 'kecamatan' => 2, 'kelurahan' => 2, 'mahasiswa' => 3, 'dpl' => 1, 'kelompok' => 2], $table['total']);
        $this->assertSame(1, $rows[$this->lokasi->nama_lokasi]['pt']);
    }

    public function test_realisasi_is_not_capped_and_non_selesai_is_ignored(): void
    {
        Kpicapaian::query()->update(['realisasi' => 500]);
        $service = app(KpiRekapService::class);

        $pt1 = $service->rekapPerPt(['kodept' => $this->pt1->npsn])->first();
        $this->assertEquals(500, $pt1->realisasi);
        $this->assertEquals(100, $pt1->capaian);

        $pt2 = $service->rekapPerPt(['kodept' => $this->pt2->npsn])->first();
        $this->assertNull($pt2->realisasi);
        $this->assertNull($pt2->capaian);
    }

    public function test_admin_home_matches_kepala_and_keeps_admin_cards(): void
    {
        $this->loginAs('admin');

        $this->get('home')->assertOk()
            ->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === true)
            ->assertSee('Konversi Nilai')->assertSee('Universitas Satu');
    }

    public function test_dashboard_filter_returns_partial_for_ajax(): void
    {
        $this->loginAs('kepala');

        $this->get('dashboardkpi?kodept='.$this->pt1->npsn, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('kpidashboard._content')
            ->assertSee('Isian Capaian KPI oleh Ketua Kelompok')->assertDontSee('kpi-filter');
        $this->get('dashboardkpi')->assertOk()->assertViewIs('kpidashboard.index')->assertDontSee('Rata-rata Capaian KPI</td>', false);
    }

    public function test_pt_dpl_count_uses_dpl_kodept(): void
    {
        Dpl::factory()->count(2)->create(['kodept' => $this->pt1->npsn]);
        Dpl::factory()->create(['kodept' => $this->pt2->npsn]);
        $this->loginAs('pt', ['email' => $this->pt1->npsn]);

        $this->get('home')->assertOk()->assertViewHas('jumlahdpl', 2);
    }

    public function test_kepala_home_shows_totals_for_all_pt(): void
    {
        $this->loginAs('kepala');

        $this->get('home')->assertOk()
            ->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === true && $k['capaian']->pluck('kodept')->unique()->count() === 2)
            ->assertSee('Universitas Satu')->assertSee('Politeknik Dua')->assertSee('Belum memilih lokasi');
        $this->get('dashboardkpi?kodept='.$this->pt2->npsn)->assertOk()
            ->assertViewHas('rekapPerPt', fn ($rows) => $rows->pluck('kodept')->unique()->all() === [$this->pt2->npsn]);
    }

    public function test_pt_home_shows_capaian_per_kegiatan(): void
    {
        $this->loginAs('pt', ['email' => $this->pt1->npsn, 'location_program' => $this->lokasi->id]);

        $this->get('home')->assertOk()
            ->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === false && $k['capaian']->first()->capaian == 100)
            ->assertSee('Capaian per KPI');
    }

    public function test_pt_is_locked_to_its_own_pt_but_not_to_account_lokasi(): void
    {
        $lokasiAkun = LokasiProgram::factory()->create();
        $this->loginAs('pt', ['email' => $this->pt1->npsn, 'location_program' => $lokasiAkun->id]);

        $this->get('dashboardkpi?kodept='.$this->pt2->npsn)->assertOk()
            ->assertViewHas('filter', fn ($f) => $f['kodept'] === $this->pt1->npsn && $f['lokasi'] === null)
            ->assertViewHas('rekapPerPt', fn ($rows) => $rows->pluck('kodept')->unique()->all() === [$this->pt1->npsn])
            ->assertSee('Belum diisi');
    }

    public function test_dashboard_rejects_invalid_filter(): void
    {
        $this->loginAs('kepala');

        $this->get('dashboardkpi?lokasi=bukan-uuid')->assertSessionHasErrors('lokasi');
    }

    public function test_kepala_cannot_access_admin_or_mahasiswa_routes(): void
    {
        $this->loginAs('kepala');

        $this->get('kpitarget')->assertRedirect(route('home'));
        $this->get('user')->assertRedirect(route('home'));
        $this->get('admlaporandpl')->assertRedirect(route('home'));
        $this->put('kpicapaian/insert', [])->assertForbidden();
    }

    public function test_kepala_opens_pt_menu_read_only(): void
    {
        $this->loginAs('kepala');

        foreach (['lapcapaiankpi', 'ptdpl', 'ptmahasiswa', 'admlogkegiatan', 'admlogbulanan', 'admlogkehadiran', 'admevaluasikegiatan'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson('ptmahasiswa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 3);

        // Konversi nilai & laporan akhir dicabut untuk kepala
        $this->get('dplkonversinilai')->assertRedirect(route('home'));
        $this->get('pttugasakhir')->assertRedirect(route('home'));

        $this->put('admlogbulanan/updatenilai', [])->assertForbidden();
        $this->put('ptevaluasikegiatan/insert', [])->assertForbidden();
        $this->put('dplkonversinilai/destroy', [])->assertForbidden();
        $this->put('profile/update', [])->assertStatus(200);
        $this->put('setting/update', [])->assertJsonValidationErrors('plama', 'errors');
    }

    public function test_only_admin_can_export_kpi_rekap(): void
    {
        Excel::fake();
        Excel::matchByRegex();
        $this->loginAs('admin');

        $this->get('dashboardkpi')->assertOk()->assertSee('id="kpi-export"', false);
        $this->get('dashboardkpi/export?kodept='.$this->pt1->npsn)->assertOk();
        Excel::assertDownloaded(
            '/^rekap_kpi_.+\.xlsx$/',
            fn (KpiRekapExport $export) => count($export->sheets()) === 4
                && $export->sheets()[2]->collection()->pluck(0)->unique()->all() === ['Universitas Satu']
        );

        $this->loginAs('kepala');
        $this->get('dashboardkpi')->assertOk()->assertDontSee('id="kpi-export"', false);
        $this->get('dashboardkpi/export')->assertRedirect(route('home'));

        $this->loginAs('pt', ['email' => $this->pt1->npsn]);
        $this->get('dashboardkpi/export')->assertRedirect(route('home'));
    }

    public function test_dpl_and_mahasiswa_cannot_open_dashboard(): void
    {
        $this->loginAs('mahasiswa');

        $this->get('dashboardkpi')->assertRedirect(route('home'));
    }

    public function test_admin_can_create_and_update_kepala_user(): void
    {
        $this->loginAs('admin');

        $this->put('user/insertuserkepala', [
            'name' => 'Kepala LLDIKTI',
            'email' => 'kepala@pps.test',
            'password' => 'rahasia123',
            'role' => 'admin',
        ])->assertJson(['success' => true]);

        $kepala = User::where('email', 'kepala@pps.test')->firstOrFail();
        $this->assertSame('kepala', $kepala->role);

        $this->put('user/insertuserkepala', ['name' => 'X', 'email' => 'kepala@pps.test', 'password' => 'rahasia123'])
            ->assertJsonValidationErrors('email', 'errors');

        $pt = User::factory()->role('pt')->create();
        $this->put('user/updateuserkepala', ['id' => $pt->id, 'name' => 'X', 'email' => 'x@pps.test'])
            ->assertJsonValidationErrors('id', 'errors');

        $this->put('user/updateuserkepala', ['id' => $kepala->id, 'name' => 'Kepala Baru', 'email' => 'kepala@pps.test'])
            ->assertJson(['success' => true]);
        $this->assertSame('Kepala Baru', $kepala->fresh()->name);
    }
}
