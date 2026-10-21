<?php

namespace Tests\Feature\Auth;

use App\Models\LokasiProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginLokasiSelectTest extends TestCase
{
    use RefreshDatabase;

    private LokasiProgram $bandung;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bandung = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Cimahi']);
    }

    private function makeUser(string $role, ?LokasiProgram $lokasi = null): User
    {
        return User::factory()->role($role)->create([
            'email' => $role.'@pps.test',
            'password' => Hash::make('Rahasia123'),
            'location_program' => $lokasi?->id,
        ]);
    }

    private function selectedOptions(string $html): array
    {
        preg_match_all('/<option value="([^"]*)"\s+selected/i', $html, $m);

        return $m[1];
    }

    // AC2: form-only
    public function test_login_page_is_form_only_with_lokasi_select_inside_form(): void
    {
        $html = $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('login')
            ->assertViewMissing('laporan')
            ->assertViewMissing('dataPesertaList')
            ->assertViewMissing('panduanList')
            ->assertDontSee('data-drilldown', false)
            ->assertDontSee('id="pesertaTbody"', false)
            ->assertDontSee('id="petaSebaran"', false)
            ->assertDontSee('class="panduan-item"', false)
            ->assertSee('href="'.route('landing').'"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('#<form[^>]*id="formAuthentication"[^>]*>.*<select[^>]*name="lokasi".*</form>#s', $html);
        $this->assertSame(1, substr_count($html, 'name="lokasi"'));
        $this->assertStringContainsString('<option value="Kota Bandung"', $html);
        $this->assertStringContainsString('<option value="Kota Cimahi"', $html);
        $this->assertSame([], $this->selectedOptions($html));
    }

    public function test_lokasi_query_preselects_option_case_insensitive(): void
    {
        foreach (['Kota Bandung', 'kota bandung', 'KOTA BANDUNG', '  kOtA bAnDuNg  '] as $query) {
            $res = $this->get(route('login', ['lokasi' => $query]))->assertOk()->assertViewHas('selectedLokasi', 'Kota Bandung');
            $this->assertSame(['Kota Bandung'], $this->selectedOptions($res->getContent()), $query);
        }
    }

    public function test_unknown_lokasi_query_is_ignored_and_not_echoed(): void
    {
        foreach (['Kota Zzzuji', '"><script>alert(1)</script>', 'Kota Bandung Barat'] as $query) {
            $res = $this->get(route('login', ['lokasi' => $query]))->assertOk()->assertViewHas('selectedLokasi', null);
            $html = $res->getContent();
            $this->assertSame([], $this->selectedOptions($html), $query);
            $this->assertStringNotContainsString('Zzzuji', $html);
            $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
            $this->assertStringNotContainsString('alert(1)', $html);
            $this->assertStringNotContainsString('Kota Bandung Barat', $html);
        }
    }

    public function test_array_lokasi_query_does_not_error(): void
    {
        foreach (['login?lokasi[]=Kota%20Bandung', 'login?lokasi[]=x', 'login?lokasi[a][b]=x'] as $url) {
            $this->get($url)->assertOk()->assertViewHas('selectedLokasi', null);
        }
    }

    // AC2: login tetap berfungsi
    public function test_admin_login_without_lokasi(): void
    {
        $this->makeUser('admin');

        $this->put('login', ['username' => 'admin@pps.test', 'password' => 'Rahasia123', 'lokasi' => ''])
            ->assertJson(['success' => true, 'redirect_url' => url('home')]);
        $this->assertAuthenticated();
    }

    public function test_dpl_must_choose_lokasi_from_select(): void
    {
        $this->makeUser('dpl', $this->bandung);

        $this->put('login', ['username' => 'dpl@pps.test', 'password' => 'Rahasia123', 'lokasi' => ''])
            ->assertJson(['success' => false, 'messages' => 'Silakan pilih lokasi program terlebih dahulu']);
        $this->assertGuest();

        $this->put('login', ['username' => 'dpl@pps.test', 'password' => 'Rahasia123', 'lokasi' => 'Kota Cimahi'])
            ->assertJson(['success' => false, 'messages' => 'Lokasi program anda tidak valid!']);
        $this->assertGuest();

        $this->put('login', ['username' => 'dpl@pps.test', 'password' => 'Rahasia123', 'lokasi' => 'Kota Bandung'])
            ->assertJson(['success' => true, 'redirect_url' => url('home/kota%20bandung')]);
        $this->assertAuthenticated();
        $this->assertSame('Kota Bandung', session('lokasi_program'));
    }

    public function test_mahasiswa_must_choose_lokasi_from_select(): void
    {
        $this->makeUser('mahasiswa', $this->bandung);

        $this->put('login', ['username' => 'mahasiswa@pps.test', 'password' => 'Rahasia123'])
            ->assertJson(['success' => false, 'messages' => 'Silakan pilih lokasi program terlebih dahulu']);
        $this->assertGuest();

        $this->put('login', ['username' => 'mahasiswa@pps.test', 'password' => 'Rahasia123', 'lokasi' => 'Kota Bandung'])
            ->assertJson(['success' => true, 'redirect_url' => url('home/kota%20bandung')]);
        $this->assertAuthenticated();
    }
}
