<?php

namespace Tests\Feature;

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

        // PT1: 2 kelompok (90 => 100%, belum isi => 0%), PT2: 1 kelompok (40 => 50%)
        $this->ketua($this->pt1, 90);
        $this->ketua($this->pt1, null);
        $this->ketua($this->pt2, 40);
    }

    private function ketua(Satuanpendidikan $pt, ?float $realisasi): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $this->lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        if ($realisasi !== null) {
            Kpicapaian::factory()->create([
                'email' => $mhs->email,
                'id_kpi' => $this->target->id_kpi,
                'id_target' => $this->target->id_target,
                'realisasi' => $realisasi,
                'satuan' => '%',
            ]);
        }

        return $mhs;
    }

    public function test_capaian_is_capped_and_unfilled_groups_count_as_zero(): void
    {
        $service = app(KpiRekapService::class);
        $rows = $service->rekapPerPt([])->keyBy('kodept');

        $this->assertSame(2, (int) $rows[$this->pt1->npsn]->jumlah_kelompok);
        $this->assertSame(1, (int) $rows[$this->pt1->npsn]->jumlah_mengisi);
        $this->assertEquals(50, $rows[$this->pt1->npsn]->capaian);
        $this->assertEquals(50, $rows[$this->pt2->npsn]->capaian);

        $summary = $service->summary([]);
        $this->assertSame(2, $summary['jumlah_pt']);
        $this->assertSame(3, $summary['total_kelompok']);
        $this->assertArrayNotHasKey('rata_capaian', $summary);
        $this->assertEquals(50, $service->rekapPerKegiatan([])->first()->capaian);
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

    public function test_average_realisasi_never_exceeds_target(): void
    {
        // Data lama yang terlanjur melebihi target tetap dibatasi di rekap
        Kpicapaian::query()->update(['realisasi' => 500]);

        $row = app(KpiRekapService::class)->rekapPerPt(['kodept' => $this->pt2->npsn])->first();

        $this->assertEquals(80, $row->realisasi);
        $this->assertEquals(100, $row->capaian);
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
            ->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === false && $k['capaian']->first()->capaian == 50);
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

        foreach (['lapcapaiankpi', 'dplkonversinilai', 'pttugasakhir', 'admlogkegiatan', 'admlogbulanan', 'admlogkehadiran', 'admevaluasikegiatan'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson('pttugasakhir/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 3);

        $this->put('admlogbulanan/updatenilai', [])->assertForbidden();
        $this->put('ptevaluasikegiatan/insert', [])->assertForbidden();
        $this->put('dplkonversinilai/destroy', [])->assertForbidden();
        $this->put('profile/update', [])->assertStatus(200);
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
