<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            ->assertSeeInOrder(['Kelurahan/Desa</th>', 'BaseLine</th>', 'Pengurangan Sampah (%)</th>', 'Klaster</th>'], false)
            ->assertSeeInOrder(['Desa Berbaseline</a>', '12,50%</td>', '20,00%</td>'], false)
            ->assertSeeInOrder(['Desa Tanpa Baseline</a>', '-</td>', '20,00%</td>'], false);
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
            ->assertSeeInOrder(['BaseLine</th>', 'Desa Berbaseline</a>', '1.250,00 kg</td>', '20,00%</td>'], false)
            ->assertSeeInOrder(['Desa Tanpa Baseline</a>', '-</td>'], false)
            ->assertDontSee('1.250,00%');
    }
}
