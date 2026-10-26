<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Faq;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PelaksanaanKkn;
use App\Models\PendataanPemilahanSampah;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Services\PetaSebaranService;
use App\Services\SebaranPtSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_ROUTES = [
        'login.laporan' => 'login/laporan',
        'login.peta' => 'login/peta',
        'login.peta.filter' => 'login/peta/filter',
        'login.laporan.export' => 'login/laporan/export',
        'login.data-peserta.export' => 'login/data-peserta/export',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // Kecamatan + 2 desa dengan penurunan timbulan 20% = 1 - 80 / (1 × 100) (baseline diisi & kosong)
    private function wilayahDenganSampah(): Kecamatan
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji', 'koefisien_timbulan_sampah' => 1]);
        $kecamatan = Kecamatan::factory()->create(['kecamatan' => 'Kec Uji']);
        $pt = Satuanpendidikan::factory()->create();
        $bulan = now()->format('Y-m');

        foreach (['Desa Berbaseline' => 12.5, 'Desa Tanpa Baseline' => null] as $nama => $baseline) {
            $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $nama, 'baseline' => $baseline]);
            $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);
            Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
            Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email]);
            PendataanPemilahanSampah::factory()->create([
                'email' => $mhs->email, 'tanggal' => $bulan.'-01', 'jumlah_anggota_keluarga' => 100,
                'organik_kg' => 0, 'anorganik_kg' => 0, 'residu_kg' => 80,
            ]);
        }

        return $kecamatan;
    }

    private function assertBaselineBeforePersen($response): void
    {
        $response->assertOk()
            ->assertSeeInOrder(['Kelurahan/Desa<i class="ri-arrow-up-down-line"', 'Baseline</th>', '>Penurunan Timbulan Sampah (%)<i class="ri-arrow-up-down-line"', 'Klaster<i class="ri-arrow-up-down-line"'], false)
            ->assertSeeInOrder(['Desa Berbaseline</a>', '12,50%</td>', '20,00%</td>'], false)
            ->assertSeeInOrder(['Desa Tanpa Baseline</a>', '-</td>', '20,00%</td>'], false)
            ->assertDontSee('Pengurangan Timbulan Sampah (%)', false)
            ->assertDontSee('% Pengurangan Sampah', false)
            ->assertDontSee('Persentase Penurunan Timbulan Sampah</th>', false);
        $this->assertMatchesRegularExpression('/Penurunan Timbulan Sampah \(%\)<i class="ri-arrow-up-down-line" aria-hidden="true"><\/i><\/button>\s*<\/th>/', $response->getContent());
    }

    // AC1
    public function test_guest_sees_landing_with_masuk_button_and_public_sections(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('landing')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Masuk')
            ->assertDontSee('Ke Dashboard')
            ->assertDontSee('lp-fab', false)
            ->assertSeeInOrder(['id="lpOffcanvas"', 'href="'.route('login').'"', 'Masuk'], false)
            ->assertSee('class="btn btn-primary lp-cta d-none d-sm-inline-flex"', false)
            ->assertSee('<a href="#lokasi" class="btn btn-lg lp-btn-ghost">Pilih Lokasi &amp; Masuk', false)
            ->assertSee('data-drilldown="'.route('login.laporan').'"', false)
            ->assertSee('id="peserta"', false)
            ->assertSee('data-url="'.route('login.peta').'"', false)
            ->assertSee('id="dokumen"', false)
            ->assertViewHas('laporan', fn ($l) => ($l['publik'] ?? false) === true);
    }

    public function test_authenticated_user_sees_landing_with_dashboard_button(): void
    {
        $this->loginAs('mahasiswa');

        $this->get('/')
            ->assertOk()
            ->assertViewIs('landing')
            ->assertSee('Ke Dashboard')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSee('href="'.route('login').'"', false)
            ->assertDontSee('lp-fab', false)
            ->assertSeeInOrder(['id="lpOffcanvas"', 'href="'.route('home').'"', 'Ke Dashboard', 'action="'.route('logout').'"', 'Keluar'], false)
            ->assertSee('href="'.route('home').'" class="btn btn-primary lp-cta d-none d-sm-inline-flex"', false)
            ->assertSee('href="'.route('home').'" class="btn btn-lg lp-btn-ghost d-sm-none">Ke Dashboard', false)
            ->assertSee('data-drilldown="'.route('login.laporan').'"', false)
            ->assertSee('id="peserta"', false)
            ->assertSee('id="dokumen"', false);
    }

    public function test_landing_lokasi_card_links_to_login_with_lokasi_query(): void
    {
        LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);

        $this->get('/')->assertOk()
            ->assertSee('href="'.e(route('login', ['lokasi' => 'Kota Bandung'])).'"', false);
    }

    // AC3
    public function test_public_routes_keep_name_and_url(): void
    {
        foreach (self::PUBLIC_ROUTES as $name => $uri) {
            $this->assertSame(url($uri), route($name), $name);
        }
    }

    public function test_public_endpoints_accessible_by_guest_and_authenticated_user(): void
    {
        $check = function (string $label): void {
            $this->get(route('login.laporan'))->assertOk()->assertViewIs('laporan._capaian_publik');
            $this->getJson(route('login.peta'))->assertOk()->assertJsonStructure(['desa', 'mode', 'summary']);
            $this->getJson(route('login.peta.filter'))->assertOk();
            foreach (['login.laporan.export', 'login.data-peserta.export'] as $export) {
                $res = $this->get(route($export))->assertOk();
                $this->assertStringContainsString('spreadsheetml', (string) $res->headers->get('content-type'), $label.' '.$export);
            }
        };

        $check('guest');
        foreach (['admin', 'pt', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $check($role);
        }
    }

    public function test_authenticated_user_still_blocked_from_guest_only_login_routes(): void
    {
        $this->loginAs('admin');

        $this->get(route('login'))->assertRedirect(route('home'));
        $this->get(route('login.token'))->assertRedirect(route('home'));
        $this->put('login', ['username' => 'x@x.test', 'password' => 'x'])->assertRedirect(route('home'));
    }

    public function test_public_laporan_shows_public_export_for_authenticated_non_admin(): void
    {
        $this->loginAs('pt');

        $this->get(route('login.laporan'))->assertOk()
            ->assertSee(route('login.laporan.export'), false)
            ->assertDontSee('export-capaian', false);
    }

    // AC5
    public function test_landing_drilldown_shows_baseline_column_before_persentase(): void
    {
        $kec = $this->wilayahDenganSampah();

        $this->assertBaselineBeforePersen($this->get(route('login.laporan', ['kecamatan' => $kec->id_kecamatan])));
    }

    public function test_dashboard_drilldown_shows_baseline_column_before_persentase(): void
    {
        $kec = $this->wilayahDenganSampah();
        $this->loginAs('admin');

        $this->assertBaselineBeforePersen(
            $this->get('dashboard-pengurangan-sampah?kecamatan='.$kec->id_kecamatan, ['X-Requested-With' => 'XMLHttpRequest'])
        );
    }

    public function test_baseline_change_invalidates_public_capaian_cache(): void
    {
        $kec = $this->wilayahDenganSampah();
        $url = route('login.laporan', ['kecamatan' => $kec->id_kecamatan]);
        $this->get($url)->assertSee('12,50%');

        Desa::where('desa', 'Desa Berbaseline')->firstOrFail()->update(['baseline' => 33.33]);

        $this->get($url)->assertSee('33,33%')->assertDontSee('12,50%');
    }

    public function test_public_capaian_shows_baseline_with_its_satuan(): void
    {
        $kec = $this->wilayahDenganSampah();
        Desa::where('desa', 'Desa Berbaseline')->firstOrFail()->update(['baseline' => 1250, 'satuan_baseline' => 'kg']);

        $this->get(route('login.laporan', ['kecamatan' => $kec->id_kecamatan]))
            ->assertOk()
            ->assertSeeInOrder(['Baseline</th>', 'Desa Berbaseline</a>', '1.250,00 kg</td>', '20,00%</td>'], false)
            ->assertSeeInOrder(['Desa Tanpa Baseline</a>', '-</td>'], false)
            ->assertDontSee('1.250,00%');
    }

    // pj_desa: 2 sudah + 3 belum; PT peserta: A (mhs), B (dpl), C (mhs); pelaksanaan: A + PT non-peserta
    private function seedStatusPeserta(): array
    {
        foreach (['sudah', 'sudah', 'belum', 'belum', 'belum'] as $status) {
            Pjdesa::create(['email' => fake()->unique()->safeEmail(), 'id_desa' => Desa::factory()->create()->id_desa, 'status_mobilisasi' => $status]);
        }
        [$a, $b, $c, $luar] = Satuanpendidikan::factory()->count(4)->create()->all();
        Mahasiswa::factory()->count(2)->create(['kodept' => $a->npsn]);
        Dpl::factory()->create(['kodept' => $b->npsn]);
        Mahasiswa::factory()->create(['kodept' => $c->npsn]);
        PelaksanaanKkn::factory()->forPt($a->npsn)->create();
        PelaksanaanKkn::factory()->forPt($luar->npsn)->create();

        return [$a, $b, $c];
    }

    private function statusBlock($response): string
    {
        $html = $response->assertOk()->getContent();
        $start = strpos($html, 'class="peserta-status"');
        $this->assertNotFalse($start, 'Blok ringkasan status peserta tidak tampil');

        return substr($html, $start, strpos($html, '</dl>', $start) - $start);
    }

    private function assertStatus($response, array $mobilisasi, array $pelaksanaan): void
    {
        $response->assertViewHas('statusPeserta', fn ($s) => $s['mobilisasi'] === $mobilisasi && $s['pelaksanaan'] === $pelaksanaan);
        $block = $this->statusBlock($response);
        $text = preg_replace('/\s+/', ' ', strip_tags($block));
        $this->assertStringContainsString('Mobilisasi Kelompok Sudah '.$mobilisasi['sudah'].' Belum '.$mobilisasi['belum'], $text);
        $this->assertStringContainsString('Pelaksanaan KKN Sudah isi '.$pelaksanaan['sudah'].' Belum isi '.$pelaksanaan['belum'], $text);
    }

    private function pesertaCacheVersion(): int
    {
        return (int) Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1);
    }

    public function test_landing_shows_mobilisasi_and_pelaksanaan_summary(): void
    {
        $this->seedStatusPeserta();

        $response = $this->get('/')
            ->assertViewHas('dataPesertaList', fn ($l) => $l->count() === 3);
        $this->assertStatus($response, ['sudah' => 2, 'belum' => 3, 'total' => 5], ['sudah' => 1, 'belum' => 2, 'total' => 3]);
        $response->assertSeeInOrder(['id="pesertaTitle"', 'class="peserta-status"', 'bg-label-success', 'bg-label-danger', 'class="card lp-card"'], false);
    }

    public function test_mobilisasi_counts_non_sudah_status_as_belum(): void
    {
        $this->seedStatusPeserta();
        DB::table('pj_desa')->where('status_mobilisasi', 'belum')->limit(1)->update(['status_mobilisasi' => 'x']);

        $this->assertStatus($this->get('/'), ['sudah' => 2, 'belum' => 3, 'total' => 5], ['sudah' => 1, 'belum' => 2, 'total' => 3]);
    }

    public function test_pelaksanaan_total_equals_pt_in_table_and_ignores_non_peserta(): void
    {
        [, $b, $c] = $this->seedStatusPeserta();
        PelaksanaanKkn::factory()->forPt($b->npsn)->create();
        PelaksanaanKkn::factory()->forPt($c->npsn)->create();

        $this->get('/')->assertViewHas('statusPeserta', fn ($s) => $s['pelaksanaan'] === ['sudah' => 3, 'belum' => 0, 'total' => 3])
            ->assertViewHas('dataPesertaList', fn ($l) => $l->count() === 3);
    }

    public function test_status_summary_is_fresh_after_data_changes(): void
    {
        [, $b] = $this->seedStatusPeserta();
        $this->assertStatus($this->get('/'), ['sudah' => 2, 'belum' => 3, 'total' => 5], ['sudah' => 1, 'belum' => 2, 'total' => 3]);

        Pjdesa::where('status_mobilisasi', 'belum')->firstOrFail()->update(['status_mobilisasi' => 'sudah']);
        PelaksanaanKkn::factory()->forPt($b->npsn)->create();

        $this->assertStatus($this->get('/'), ['sudah' => 3, 'belum' => 2, 'total' => 5], ['sudah' => 2, 'belum' => 1, 'total' => 3]);
    }

    public function test_pjdesa_email_or_desa_change_flushes_peta_cache_but_status_toggle_does_not(): void
    {
        // Reload: like a request, wasRecentlyCreated is false
        $pj = Pjdesa::create(['email' => 'ketua@pps.test', 'id_desa' => Desa::factory()->create()->id_desa])->fresh();
        $desaBaru = Desa::factory()->create();
        Cache::forever(PetaSebaranService::VERSION_CACHE_KEY, 5);

        $pj->update(['status_mobilisasi' => 'sudah']);
        $pj->update(['status_mobilisasi' => 'belum']);
        $this->assertSame(5, $this->pesertaCacheVersion());

        $pj->update(['id_desa' => $desaBaru->id_desa]);
        $this->assertSame(6, $this->pesertaCacheVersion());

        $pj->update(['email' => 'ketua-baru@pps.test']);
        $this->assertSame(7, $this->pesertaCacheVersion());

        $pj->delete();
        $this->assertSame(8, $this->pesertaCacheVersion());
    }

    public function test_status_summary_hidden_when_both_totals_zero(): void
    {
        $this->get('/')->assertOk()
            ->assertViewHas('statusPeserta', fn ($s) => $s['mobilisasi']['total'] === 0 && $s['pelaksanaan']['total'] === 0)
            ->assertDontSee('peserta-status', false)
            ->assertDontSee('Mobilisasi Kelompok');
    }

    public function test_status_summary_shown_when_only_one_total_nonzero(): void
    {
        Mahasiswa::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);

        $this->assertStatus($this->get('/'), ['sudah' => 0, 'belum' => 0, 'total' => 0], ['sudah' => 0, 'belum' => 1, 'total' => 1]);
    }

    public function test_status_badges_open_modal_only_when_count_positive(): void
    {
        [, $b, $c] = $this->seedStatusPeserta();
        PelaksanaanKkn::factory()->forPt($b->npsn)->create();
        PelaksanaanKkn::factory()->forPt($c->npsn)->create();
        Mahasiswa::factory()->create(['kodept' => $b->npsn, 'location_program' => LokasiProgram::factory()->create()->id]);

        $response = $this->get('/')->assertOk()
            ->assertViewHas('statusPeserta', fn ($s) => $s['pelaksanaan'] === ['sudah' => 3, 'belum' => 0, 'total' => 3])
            ->assertViewHas('statusLokasi', ['sudah' => 0, 'belum' => 1, 'total' => 1]);
        $html = $response->getContent();
        $button = fn (string $jenis, string $status) => '/<button type="button" class="badge bg-label-(success|danger) status-badge-btn" data-jenis="'.$jenis.'" data-status="'.$status.'"[^>]*aria-controls="modalStatusList">/';

        $response->assertViewHas('statusKetua', ['sudah' => 0, 'belum' => 1, 'total' => 1]);
        foreach ([['mobilisasi', 'sudah'], ['mobilisasi', 'belum'], ['pelaksanaan', 'sudah'], ['lokasi', 'belum'], ['ketua', 'belum']] as [$jenis, $status]) {
            $this->assertMatchesRegularExpression($button($jenis, $status), $html, "$jenis/$status harus button");
        }
        foreach ([['pelaksanaan', 'belum'], ['lokasi', 'sudah'], ['ketua', 'sudah']] as [$jenis, $status]) {
            $this->assertDoesNotMatchRegularExpression('/data-jenis="'.$jenis.'" data-status="'.$status.'"/', $html, "$jenis/$status (0) bukan button");
        }
        $this->assertSame(5, substr_count($html, 'status-badge-btn"'));
        $text = preg_replace('/\s+/', ' ', strip_tags($this->statusBlock($response)));
        $this->assertStringContainsString('Belum isi 0', $text);

        $response->assertSee('id="modalStatusList"', false)
            ->assertSee('data-url="'.route('login.status-list').'"', false);
    }

    // PT A: mhs ketua + mhs biasa; B: mhs tanpa ketua; C: hanya DPL; D: mhs+DPL tanpa lokasi/kodept (diabaikan)
    private function seedStatusKetua(): array
    {
        $lokasi = LokasiProgram::factory()->create()->id;
        [$a, $b, $c, $d] = Satuanpendidikan::factory()->count(4)->create()->all();
        $ketua = Mahasiswa::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi]);
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        Mahasiswa::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi]);
        Dpl::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi]);
        Mahasiswa::factory()->create(['kodept' => $b->npsn, 'location_program' => $lokasi]);
        Dpl::factory()->create(['kodept' => $c->npsn, 'location_program' => $lokasi]);
        $tanpaLokasi = Mahasiswa::factory()->create(['kodept' => $d->npsn]);
        Pjdesa::create(['email' => $tanpaLokasi->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        Mahasiswa::factory()->create(['location_program' => $lokasi]);
        Dpl::factory()->create(['kodept' => $d->npsn]);
        Dpl::factory()->create(['location_program' => $lokasi]);

        return [$a, $b, $c, $d];
    }

    private function sebaranStatusBlock($response): ?string
    {
        $html = $response->assertOk()->getContent();
        $section = strpos($html, 'id="sebaran"');
        $this->assertNotFalse($section);
        $start = strpos($html, 'class="peserta-status"', $section);
        $end = strpos($html, '</section>', $section);
        if ($start === false || $start > $end) {
            return null;
        }

        return substr($html, $start, strpos($html, '</dl>', $start) - $start);
    }

    public function test_status_ketua_counts_pt_with_pjdesa_mahasiswa_as_sudah(): void
    {
        $this->seedStatusKetua();

        $this->assertSame(['sudah' => 1, 'belum' => 2, 'total' => 3], app(SebaranPtSummary::class)->statusKetua());
    }

    public function test_status_ketua_is_fresh_after_pjdesa_added(): void
    {
        [, $b] = $this->seedStatusKetua();
        $this->get('/')->assertViewHas('statusKetua', ['sudah' => 1, 'belum' => 2, 'total' => 3]);

        $mhs = Mahasiswa::where('kodept', $b->npsn)->firstOrFail();
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        $this->get('/')->assertViewHas('statusKetua', ['sudah' => 2, 'belum' => 1, 'total' => 3]);
    }

    private function ketuaItem(?string $block): string
    {
        $this->assertNotNull($block, 'Blok status sebaran tidak tampil');
        $ketua = substr($block, strpos($block, 'PTS Set Ketua Kelompok'));

        return substr($ketua, 0, strpos($ketua, '</div>'));
    }

    public function test_landing_ketua_badges_are_modal_buttons_when_count_positive(): void
    {
        $this->seedStatusKetua();

        $response = $this->get('/')->assertViewHas('statusKetua', ['sudah' => 1, 'belum' => 2, 'total' => 3]);
        $ketua = $this->ketuaItem($this->sebaranStatusBlock($response));
        $text = preg_replace('/\s+/', ' ', strip_tags($ketua));
        $this->assertStringContainsString('PTS Set Ketua Kelompok Sudah 1 Belum 2', $text);

        $this->assertSame(2, substr_count($ketua, 'status-badge-btn"'));
        $this->assertStringNotContainsString('<span class="badge', $ketua);
        foreach (['sudah' => 'Sudah (1)', 'belum' => 'Belum (2)'] as $status => $title) {
            $this->assertMatchesRegularExpression(
                '/<button type="button" class="badge bg-label-(success|danger) status-badge-btn" data-jenis="ketua" data-status="'.$status.'"\s+data-title="PTS Set Ketua Kelompok - '.preg_quote($title, '/').'" aria-haspopup="dialog" aria-controls="modalStatusList">/',
                $ketua,
                "ketua/$status harus button"
            );
        }
    }

    public function test_landing_ketua_badge_is_static_span_when_count_zero(): void
    {
        Dpl::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn, 'location_program' => LokasiProgram::factory()->create()->id]);

        $response = $this->get('/')->assertViewHas('statusKetua', ['sudah' => 0, 'belum' => 1, 'total' => 1]);
        $ketua = $this->ketuaItem($this->sebaranStatusBlock($response));

        $this->assertMatchesRegularExpression('/<span class="badge bg-label-success"><i class="ri-check-line" aria-hidden="true"><\/i> Sudah 0<\/span>/', $ketua);
        $this->assertDoesNotMatchRegularExpression('/data-jenis="ketua" data-status="sudah"/', $ketua);
        $this->assertMatchesRegularExpression('/data-jenis="ketua" data-status="belum"/', $ketua);
        $this->assertSame(1, substr_count($ketua, 'status-badge-btn"'));
    }

    public function test_ketua_shown_without_lokasi_item_when_only_dpl(): void
    {
        Dpl::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn, 'location_program' => LokasiProgram::factory()->create()->id]);

        $response = $this->get('/')
            ->assertViewHas('statusLokasi', ['sudah' => 0, 'belum' => 0, 'total' => 0])
            ->assertViewHas('statusKetua', ['sudah' => 0, 'belum' => 1, 'total' => 1]);
        $text = preg_replace('/\s+/', ' ', strip_tags((string) $this->sebaranStatusBlock($response)));
        $this->assertStringContainsString('PTS Set Ketua Kelompok Sudah 0 Belum 1', $text);
        $this->assertStringNotContainsString('Mahasiswa Set Lokasi', $text);
    }

    public function test_ketua_item_hidden_when_total_zero(): void
    {
        // Hanya data tanpa location_program/kodept: tidak masuk sebaran
        $pt = Satuanpendidikan::factory()->create()->npsn;
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        Dpl::factory()->create(['location_program' => LokasiProgram::factory()->create()->id]);

        $response = $this->get('/')->assertViewHas('statusKetua', ['sudah' => 0, 'belum' => 0, 'total' => 0]);
        $response->assertDontSee('PTS Set Ketua Kelompok');
        $this->assertNull($this->sebaranStatusBlock($response));
    }

    public function test_faq_section_and_nav_hidden_when_no_faq(): void
    {
        $this->get('/')->assertOk()
            ->assertViewHas('faqList', fn ($list) => $list->isEmpty())
            ->assertDontSee('id="faq"', false)
            ->assertDontSee('href="#faq"', false)
            ->assertDontSee('Pertanyaan yang Sering Diajukan');
    }

    public function test_faq_section_and_nav_shown_as_collapsed_accordion_in_creation_order(): void
    {
        Faq::factory()->create(['pertanyaan' => 'Pertanyaan pertama?', 'jawaban' => "Baris satu\nBaris dua"]);
        Faq::factory()->create(['pertanyaan' => 'Pertanyaan kedua?']);

        $response = $this->get('/')->assertOk()
            ->assertSee('href="#faq"', false)
            ->assertSee('id="faq"', false)
            ->assertSee('Pertanyaan yang Sering Diajukan')
            ->assertSeeInOrder(['id="dokumen"', 'id="faq"', '<footer'], false)
            ->assertSeeInOrder(['Pertanyaan pertama?', 'Pertanyaan kedua?'])
            ->assertSee('<div class="accordion-body faq-answer">Baris satu'."\n".'Baris dua</div>', false)
            ->assertDontSee('accordion-collapse collapse show', false);

        $this->assertSame(2, substr_count($response->getContent(), 'aria-expanded="false"'));
        $this->assertStringContainsString('white-space: pre-line', (string) file_get_contents(public_path('css/landing.css')));
    }

    public function test_faq_html_is_escaped_on_landing(): void
    {
        Faq::factory()->create(['pertanyaan' => '<script>alert(1)</script>', 'jawaban' => '<img src=x onerror=alert(2)>']);

        $this->get('/')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(2)&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false);
    }

    public function test_faq_list_is_cached_and_refreshed_after_model_change(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => 'Lama?']);
        $this->get('/')->assertOk()->assertSee('Lama?');
        $this->assertTrue(Cache::has(Faq::PUBLIC_CACHE_KEY));

        $faq->update(['pertanyaan' => 'Baru?']);
        $this->get('/')->assertOk()->assertSee('Baru?')->assertDontSee('Lama?');

        $faq->delete();
        $this->get('/')->assertOk()->assertDontSee('Baru?')->assertDontSee('id="faq"', false);
    }
}
