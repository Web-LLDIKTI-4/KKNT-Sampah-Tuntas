<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Pjdesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PemdaRoleTest extends TestCase
{
    use RefreshDatabase;

    private const SKIP = ['logout', 'login', 'sanctum/csrf-cookie', 'up'];

    // Semua route GET tanpa parameter
    private function getUris(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{'))
            ->map(fn ($r) => $r->uri())
            ->reject(fn ($uri) => in_array($uri, self::SKIP, true) || str_starts_with($uri, '_'))
            ->unique()->values()->all();
    }

    private function statuses(string $role): array
    {
        $this->loginAs($role);
        $hasil = [];
        foreach ($this->getUris() as $uri) {
            $hasil[$uri] = $this->get($uri)->getStatusCode();
        }

        return $hasil;
    }

    public function test_pemda_get_access_matches_kepala_on_every_route(): void
    {
        Excel::fake();

        // Pemda boleh mengelola profil desa, kepala tidak
        $kepala = array_diff_key($this->statuses('kepala'), ['desaprofile/tambah' => true]);
        $pemda = array_diff_key($this->statuses('pemda'), ['desaprofile/tambah' => true]);

        $this->assertSame($kepala, $pemda);
    }

    public function test_pemda_write_requests_are_forbidden_like_kepala(): void
    {
        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->put('admlogbulanan/updatenilai', [])->assertForbidden();
            $this->put('ptevaluasikegiatan/insert', [])->assertForbidden();
            $this->post('dpllaporan/tambah', [])->assertForbidden();
            $this->put('kpicapaian/insert', [])->assertForbidden();
            $this->put('profile/update', [])->assertStatus(200);
        }
    }

    public function test_pemda_cannot_reach_admin_routes(): void
    {
        $this->loginAs('pemda');

        $this->get('user')->assertRedirect(route('home'));
        $this->get('dplkonversinilai')->assertRedirect(route('home'));
        $this->get('pttugasakhir')->assertRedirect(route('home'));
        $this->get('dashboardkpi/export-capaian')->assertRedirect(route('home'));
    }

    public function test_pemda_home_uses_kepala_dashboard_and_menu(): void
    {
        $this->loginAs('pemda');

        $this->get('home')->assertOk()->assertViewHas('kpiHome', fn ($k) => $k['perPt'] === true);
        $this->get('dashboardkpi')->assertOk()->assertSee('id="kpi-export"', false);
    }

    public function test_admin_creates_and_edits_pemda_user(): void
    {
        $this->loginAs('admin');

        $this->put('user/insertuserkepala', ['name' => 'Pemda', 'email' => 'pemda@pps.test', 'password' => 'rahasia123', 'role' => 'pemda'])
            ->assertJson(['success' => true]);
        $pemda = User::where('email', 'pemda@pps.test')->firstOrFail();
        $this->assertSame('pemda', $pemda->role);

        $this->get('user/edituserkepala/'.$pemda->id)->assertOk()->assertSee('value="pemda" selected', false);
        $this->get('user/listdata')->assertOk()->assertSee('user/edituserkepala/'.$pemda->id, false);

        $this->put('user/updateuserkepala', ['id' => $pemda->id, 'name' => 'Pemda Baru', 'email' => 'pemda@pps.test', 'role' => 'kepala'])
            ->assertJson(['success' => true]);
        $this->assertSame('kepala', $pemda->fresh()->role);

        // Tidak boleh eskalasi ke role lain
        foreach (['admin', 'pt', 'mahasiswa', ''] as $role) {
            $this->put('user/updateuserkepala', ['id' => $pemda->id, 'name' => 'X', 'email' => 'pemda@pps.test', 'role' => $role])
                ->assertJsonValidationErrors('role', 'errors');
        }
        $this->assertSame('kepala', $pemda->fresh()->role);
    }

    public function test_kpicapaian_read_only_without_data_sampah_form(): void
    {
        $ketua = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        $this->get('kpicapaian')->assertOk()
            ->assertSee('id="resultcontent"', false)
            ->assertSee('Persentase Penurunan Sampah [(J/K)*100%]', false)
            ->assertDontSee('resultcontent-sampah', false)
            ->assertDontSee('data-sampah-form', false)
            ->assertDontSee('kpisampah', false);
        $this->get('kpisampah/listdata')->assertNotFound();
        $this->get('kpisampah/tambah')->assertNotFound();
    }
}
