<?php

namespace Tests\Feature;

use App\Exports\Sheets\DataSampahSheet;
use App\Models\Desa;
use App\Models\Dpl;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->lokasi = LokasiProgram::factory()->create();
        $this->pt1 = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Satu']);
        $this->pt2 = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Dua']);

        // PT1: 2 kelompok, PT2: 1 kelompok; tiap ketua di kelurahan (dan kecamatan) berbeda
        $this->ketua($this->pt1);
        $this->ketua($this->pt1);
        $this->ketua($this->pt2);
    }

    private function ketua(Satuanpendidikan $pt): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $this->lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $mhs;
    }

    private static function jumlahKecamatan(array $laporan): int
    {
        return $laporan['kecamatan']->sum(fn ($l) => $l->kecamatan->count());
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

    public function test_admin_home_matches_kepala_and_keeps_admin_cards(): void
    {
        $this->loginAs('admin');

        $this->get('home')->assertOk()
            ->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === true)
            ->assertSee('Konversi Nilai')->assertSee('data-drilldown=', false);
    }

    public function test_dashboard_filter_returns_partial_for_ajax(): void
    {
        $this->loginAs('kepala');

        $this->get('dashboardkpi?kodept='.$this->pt1->npsn, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('laporan._drilldown')
            ->assertSee('Sebaran Lokasi (Kecamatan)')->assertDontSee('Ringkasan');
        $this->get('dashboardkpi')->assertOk()->assertViewIs('kpidashboard.index')->assertSee('Ringkasan');
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
            ->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === true && $k['lokasi']['total']['pt'] === 2)
            ->assertSee('Belum memilih lokasi');
        $this->get('dashboardkpi')->assertOk()->assertViewHas('laporan', fn ($l) => self::jumlahKecamatan($l) === 3);
        $this->get('dashboardkpi?kodept='.$this->pt2->npsn)->assertOk()
            ->assertViewHas('laporan', fn ($l) => self::jumlahKecamatan($l) === 1);
    }

    public function test_pt_is_locked_to_its_own_pt_but_not_to_account_lokasi(): void
    {
        $lokasiAkun = LokasiProgram::factory()->create();
        $this->loginAs('pt', ['email' => $this->pt1->npsn, 'location_program' => $lokasiAkun->id]);

        $this->get('dashboardkpi?kodept='.$this->pt2->npsn)->assertOk()
            ->assertViewHas('isPt', true)
            ->assertViewHas('laporan', fn ($l) => self::jumlahKecamatan($l) === 2);
    }

    public function test_dashboard_rejects_invalid_filter(): void
    {
        $this->loginAs('kepala');

        $this->get('dashboardkpi?kecamatan=bukan-uuid')->assertSessionHasErrors('kecamatan');
    }

    public function test_kepala_cannot_access_admin_or_mahasiswa_routes(): void
    {
        $this->loginAs('kepala');

        $this->get('kpitarget')->assertNotFound();
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

    public function test_admin_and_kepala_can_export_data_sampah_but_pt_cannot(): void
    {
        Excel::fake();
        Excel::matchByRegex();

        foreach (['admin', 'kepala'] as $role) {
            $this->loginAs($role);
            $this->get('dashboardkpi')->assertOk()->assertSee('id="kpi-export"', false);
            $this->get('rekapsampah/export?klaster=merah')->assertOk();
            Excel::assertDownloaded('/^data_sampah_merah_.+\.xlsx$/', fn (DataSampahSheet $sheet) => $sheet->title() === 'Data Sampah');
        }

        $this->loginAs('pt', ['email' => $this->pt1->npsn]);
        $this->get('dashboardkpi')->assertOk()->assertDontSee('id="kpi-export"', false);
        $this->get('rekapsampah/export')->assertRedirect(route('home'));
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

    public function test_login_page_shows_laporan_kegiatan(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Capaian Program')
            ->assertSee('Persentase Pengurangan Sampah (%)')
            ->assertViewHas('laporan', fn ($l) => self::jumlahKecamatan($l) === 3
                && $l['kecamatan']->first()->nama_lokasi === $this->lokasi->nama_lokasi);
    }
}
