<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePelaksanaanKkn;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\PelaksanaanKkn;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PelaksanaanLockTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $pt;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['app.pelaksanaan_lock' => true]);
        $this->pt = Satuanpendidikan::factory()->create();
    }

    private function loginMahasiswa(?string $kodept = 'pt'): User
    {
        $user = $this->loginAs('mahasiswa');
        $user->forceFill(['password' => Hash::make('password')])->save();
        $user->mahasiswa->update(['kodept' => $kodept === 'pt' ? $this->pt->npsn : $kodept]);
        $this->actingAs($user->fresh());

        return $user;
    }

    private function loginDpl(bool $withRecord = true): User
    {
        $user = $this->loginAs('dpl');
        $user->forceFill(['password' => Hash::make('password')])->save();
        if ($withRecord) {
            Dpl::factory()->create(['email' => $user->email, 'kodept' => $this->pt->npsn]);
        }
        $this->actingAs($user->fresh());

        return $user;
    }

    private function openPelaksanaan(): PelaksanaanKkn
    {
        return PelaksanaanKkn::factory()->forPt($this->pt->npsn)->create();
    }

    private function assertLockedRedirect(TestResponse $response): void
    {
        $response->assertRedirect(route('home'))->assertSessionHas('error', RequirePelaksanaanKkn::MESSAGE);
    }

    private function assertLockedJson(TestResponse $response): void
    {
        $response->assertForbidden()->assertExactJson(['success' => false, 'message' => RequirePelaksanaanKkn::MESSAGE]);
    }

    private function assertNotLocked(TestResponse $response): void
    {
        $this->assertNotSame(403, $response->baseResponse->getStatusCode(), 'Unexpected 403');
        $this->assertNotSame(RequirePelaksanaanKkn::MESSAGE, session('error'));
        if ($response->isRedirect()) {
            $this->assertNotSame(route('home'), $response->headers->get('Location'));
        }
    }

    // --- Mahasiswa terkunci ---

    public function test_mahasiswa_locked_feature_routes_redirect_home_with_flash(): void
    {
        $this->loginMahasiswa();

        foreach (['logkegiatan', 'logkegiatan/tambah', 'logkehadiran', 'logkehadiran/tambah', 'logbulanan',
            'capaiankegiatan', 'tugasakhir', 'rencanakerja', 'desaprofile'] as $path) {
            $this->assertLockedRedirect($this->get($path));
        }
    }

    public function test_mahasiswa_locked_ajax_returns_403_json_and_nothing_saved(): void
    {
        $this->loginMahasiswa();

        $this->assertLockedJson($this->put('logkegiatan/insert', ['mode' => 'kegiatan', 'deskripsi' => 'x'], self::AJAX));
        $this->assertLockedJson($this->put('logkehadiran/insert', [], self::AJAX));
        $this->assertLockedJson($this->put('logbulanan/insert', [], self::AJAX));
        $this->assertLockedJson($this->put('capaiankegiatan/insert', [], self::AJAX));
        $this->assertLockedJson($this->put('logkegiatan/destroy', ['id' => Str::uuid()->toString()], self::AJAX));
        $this->assertLockedJson($this->getJson('logkegiatan/listdataserver?draw=1&start=0&length=10'));

        $this->assertDatabaseCount('logkegiatan', 0);
    }

    public function test_mahasiswa_locked_home_shows_lock_view_and_hides_menu(): void
    {
        $this->loginMahasiswa();

        $this->get('home')->assertOk()
            ->assertViewIs('pelaksanaankkn.terkunci')
            ->assertSee(RequirePelaksanaanKkn::MESSAGE)
            ->assertSee(url('mhsprofile'), false)
            ->assertDontSee('Tambah Log Harian')
            ->assertDontSee(url('logkegiatan').'"', false)
            ->assertDontSee(url('rencanakerja').'"', false)
            ->assertDontSee(url('desaprofile').'"', false)
            ->assertDontSee(url('logbulanan').'"', false);
    }

    public function test_mahasiswa_locked_home_follows_lokasi_redirect_then_lock_view(): void
    {
        $this->loginMahasiswa();

        $this->withSession(['lokasi_program' => 'Bandung'])->followingRedirects()->get('home')
            ->assertOk()->assertSee(RequirePelaksanaanKkn::MESSAGE);
    }

    public function test_lock_flash_not_duplicated_on_lock_view(): void
    {
        $this->loginMahasiswa();

        $html = $this->followingRedirects()->get('logkegiatan')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, e(RequirePelaksanaanKkn::MESSAGE)));
    }

    public function test_mahasiswa_locked_profile_routes_still_accessible(): void
    {
        $user = $this->loginMahasiswa();
        $desa = Desa::factory()->create();

        $this->assertNotLocked($this->get('mhsprofile')->assertOk());
        $this->assertNotLocked($this->get('mhsprofile/data')->assertOk());
        $this->assertNotLocked($this->get('mhsprofile/getPoto'));
        $this->assertNotLocked($this->get('mhsprofile/formlokasi')->assertOk());
        $this->assertNotLocked($this->get('setting'));
        $this->put('mhsprofile/update', ['nama' => 'Ani', 'nim' => '123', 'prodi' => 'SI', 'tahun_masuk' => 2022, 'phone' => '0812'], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->put('mhsprofile/setlokasi', ['id_desa' => $desa->id_desa, 'tahun' => date('Y')], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->put('setting/update', ['plama' => 'password', 'pbaru' => 'Rahasia123', 'pbaruulangi' => 'Rahasia123'], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->assertNotLocked($this->post('logout'));
        $this->assertGuest();
    }

    public function test_mahasiswa_locked_profile_hides_quick_log_links(): void
    {
        $this->loginMahasiswa();

        $this->get('mhsprofile/data')->assertOk()
            ->assertSee('Menu Cepat')
            ->assertDontSee(url('logkegiatan').'"', false)
            ->assertDontSee(url('logkehadiran').'"', false)
            ->assertDontSee(url('logbulanan').'"', false);
    }

    public function test_mahasiswa_unlocked_profile_shows_quick_log_links(): void
    {
        $this->loginMahasiswa();
        $this->openPelaksanaan();

        $this->get('mhsprofile/data')->assertOk()
            ->assertSee(url('logkegiatan').'"', false)
            ->assertSee(url('logbulanan').'"', false);
    }

    public function test_public_routes_not_locked(): void
    {
        $this->loginMahasiswa();

        $this->assertNotLocked($this->get('/'));
        $this->assertNotLocked($this->get('panduan/unduh/'.Str::uuid()));
    }

    // --- DPL terkunci ---

    public function test_dpl_locked_feature_routes_blocked(): void
    {
        $this->loginDpl();

        $this->assertLockedRedirect($this->get('dpllaporan'));
        $this->assertLockedRedirect($this->get('rencanakerja'));
        $this->assertLockedRedirect($this->get('admlogkegiatan'));
        $this->assertLockedRedirect($this->get('admlogkehadiran'));
        $this->assertLockedJson($this->get('rencanakerja/listdataserver?draw=1&start=0&length=10', self::AJAX));
        $this->assertLockedJson($this->put('dpllaporan/insert', [], self::AJAX));
        $this->assertLockedJson($this->put('rencanakerja/destroy', ['id' => Str::uuid()->toString()], self::AJAX));
    }

    public function test_dpl_locked_home_and_profile_accessible(): void
    {
        $user = $this->loginDpl();

        $this->get('home')->assertOk()->assertViewIs('pelaksanaankkn.terkunci')
            ->assertSee(url('profile'), false)
            ->assertDontSee(url('dpllaporan').'"', false)
            ->assertDontSee(url('rencanakerja').'"', false);
        $this->get('profile')->assertOk();
        $this->get('profile/data')->assertOk();
        $this->put('profile/update', ['nama' => 'Dr. Budi', 'nidn' => '0412345678', 'prodi' => 'TI', 'phone' => '0812'], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->put('setting/update', ['plama' => 'password', 'pbaru' => 'Rahasia123', 'pbaruulangi' => 'Rahasia123'], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Dr. Budi', $user->fresh()->name);
    }

    // --- Buka/tutup kunci ---

    public function test_access_opens_after_pelaksanaan_created_and_relocks_after_deleted(): void
    {
        $mhs = $this->loginMahasiswa();
        $dpl = $this->loginDpl();

        $pelaksanaan = $this->openPelaksanaan();

        $this->actingAs($mhs->fresh());
        $this->get('logkegiatan')->assertOk();
        $this->get('home')->assertOk()->assertViewIs('index-user')->assertSee(url('logkegiatan').'"', false);

        $this->actingAs($dpl->fresh());
        $this->get('dpllaporan')->assertOk();
        $this->get('home')->assertOk()->assertViewIs('index-admin');

        $pelaksanaan->delete();

        $this->actingAs($mhs->fresh());
        $this->assertLockedRedirect($this->get('logkegiatan'));
        $this->actingAs($dpl->fresh());
        $this->assertLockedRedirect($this->get('dpllaporan'));
    }

    public function test_other_pt_pelaksanaan_does_not_unlock(): void
    {
        $this->loginMahasiswa();
        PelaksanaanKkn::factory()->create();

        $this->assertLockedRedirect($this->get('logkegiatan'));
    }

    public function test_mahasiswa_without_kodept_is_locked(): void
    {
        $this->loginMahasiswa(null);
        $this->openPelaksanaan();

        $this->assertLockedRedirect($this->get('logkegiatan'));
        $this->get('home')->assertOk()->assertViewIs('pelaksanaankkn.terkunci');
    }

    public function test_dpl_without_record_is_locked_but_sees_lock_view(): void
    {
        $this->loginDpl(false);
        $this->openPelaksanaan();

        $this->assertLockedRedirect($this->get('dpllaporan'));
        $this->get('home')->assertOk()->assertViewIs('pelaksanaankkn.terkunci')->assertSee(url('profile'), false);
    }

    public function test_ketua_follows_mahasiswa_lock(): void
    {
        $user = $this->loginMahasiswa();
        $user->forceFill(['akses' => 'pjdesa'])->save();
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        $this->actingAs($user->fresh());

        $this->assertLockedRedirect($this->get('capaiankegiatan'));
        $this->openPelaksanaan();
        $this->actingAs($user->fresh());
        $this->assertNotLocked($this->get('capaiankegiatan'));
    }

    // --- Role lain & toggle ---

    public function test_other_roles_not_affected(): void
    {
        foreach (['admin', 'pt', 'kepala', 'pemda'] as $role) {
            $attributes = $role === 'pt' ? ['email' => $this->pt->npsn] : [];
            $user = $this->loginAs($role, $attributes);

            $this->assertFalse($user->fresh()->isLockedByPelaksanaan(), $role);
            $this->get('rencanakerja')->assertOk();
            $this->get('home')->assertOk()->assertViewIs('index-admin');
        }
    }

    public function test_lock_disabled_by_config(): void
    {
        config(['app.pelaksanaan_lock' => false]);
        $this->loginMahasiswa();

        $this->get('logkegiatan')->assertOk();
        $this->get('home')->assertOk()->assertViewIs('index-user');
    }

    public function test_home_runs_at_most_one_pelaksanaan_query(): void
    {
        $this->loginMahasiswa();
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, 'pelaksanaan_kkn')) {
                $count++;
            }
        });

        $this->get('home')->assertOk();

        $this->assertLessThanOrEqual(1, $count);
    }

    // --- Kunci kodept mahasiswa ---

    private function mhsPayload(array $extra = []): array
    {
        return $extra + ['nama' => 'Ani', 'nim' => '123', 'prodi' => 'SI', 'tahun_masuk' => 2022, 'phone' => '0812'];
    }

    public function test_mahasiswa_empty_kodept_can_be_filled(): void
    {
        $user = $this->loginMahasiswa(null);

        $this->put('mhsprofile/update', $this->mhsPayload(['kodept' => $this->pt->npsn]), self::AJAX)
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame($this->pt->npsn, Mahasiswa::where('email', $user->email)->value('kodept'));
    }

    public function test_mahasiswa_existing_kodept_change_is_ignored(): void
    {
        $user = $this->loginMahasiswa();
        $lain = Satuanpendidikan::factory()->create();
        PelaksanaanKkn::factory()->forPt($lain->npsn)->create();

        $this->put('mhsprofile/update', $this->mhsPayload(['nama' => 'Ani Baru', 'kodept' => $lain->npsn]), self::AJAX)
            ->assertOk()->assertJson(['success' => true]);

        $mahasiswa = Mahasiswa::where('email', $user->email)->first();
        $this->assertSame($this->pt->npsn, $mahasiswa->kodept);
        $this->assertSame('Ani Baru', $mahasiswa->nama);
        $this->assertLockedRedirect($this->get('logkegiatan'));
    }

    public function test_mahasiswa_profile_save_without_kodept_field_succeeds(): void
    {
        $user = $this->loginMahasiswa();

        $this->put('mhsprofile/update', $this->mhsPayload(['nama' => 'Tanpa PT']), self::AJAX)
            ->assertOk()->assertJson(['success' => true])->assertJsonMissingPath('errors');

        $mahasiswa = Mahasiswa::where('email', $user->email)->first();
        $this->assertSame($this->pt->npsn, $mahasiswa->kodept);
        $this->assertSame('Tanpa PT', $mahasiswa->nama);
    }

    public function test_mahasiswa_profile_form_disables_kodept_select_when_filled(): void
    {
        $this->loginMahasiswa();

        $this->get('mhsprofile/data')->assertOk()
            ->assertSee('name="kodept" data-toggle="select"  disabled', false);
    }

    public function test_mahasiswa_invalid_kodept_rejected_when_empty(): void
    {
        $this->loginMahasiswa(null);

        $this->put('mhsprofile/update', $this->mhsPayload(['kodept' => '99999999']), self::AJAX)
            ->assertJsonValidationErrors('kodept', 'errors');
    }
}
