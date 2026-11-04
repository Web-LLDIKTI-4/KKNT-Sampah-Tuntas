<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MentoringAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private const LIST = 'admmentoring/listdataserver?draw=1&start=0&length=10';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function dplUser(): array
    {
        $user = User::factory()->role('dpl')->create();
        $dpl = Dpl::factory()->create(['email' => $user->email, 'kodept' => Satuanpendidikan::factory()->create()->npsn]);

        return [$user, $dpl];
    }

    private function visibleText(string $html): string
    {
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);

        return html_entity_decode(strip_tags($html));
    }

    private function renderMenu(User $user): string
    {
        $this->actingAs($user);

        return view('layouts.menu')->render();
    }

    public function test_menu_bimbingan_mahasiswa_visible_for_admin(): void
    {
        $admin = User::factory()->role('admin')->create();
        $html = $this->renderMenu($admin);

        $this->assertStringContainsString('Bimbingan Mahasiswa', $html);
        $this->assertStringContainsString(route('admmentoring.index'), $html);
    }

    public static function nonAdminRoles(): array
    {
        return ['mahasiswa' => ['mahasiswa'], 'pt' => ['pt'], 'dpl' => ['dpl'], 'pemda' => ['pemda'], 'kepala' => ['kepala']];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_menu_bimbingan_mahasiswa_hidden_for_other_roles(string $role): void
    {
        $attrs = $role === 'pt' ? ['email' => Satuanpendidikan::factory()->create()->npsn] : [];
        $html = $this->renderMenu(User::factory()->role($role)->create($attrs));

        $this->assertStringNotContainsString('Bimbingan Mahasiswa', $html);
        $this->assertStringNotContainsString(route('admmentoring.index'), $html);
    }

    public function test_dpl_menu_unchanged(): void
    {
        [$user] = $this->dplUser();
        $html = $this->renderMenu($user);

        $this->assertStringContainsString('Laporan & Mentoring', $html);
        $this->assertStringNotContainsString('Bimbingan Mahasiswa', $html);
        $this->assertStringContainsString('Kelola Mahasiswa', $html);
        $this->assertStringContainsString(url('dplmentoring'), $html);
    }

    public function test_admin_pages_have_no_mentoring_text(): void
    {
        $this->loginAs('admin');
        $pt = Satuanpendidikan::factory()->create();
        $dpl = Dpl::factory()->create(['kodept' => $pt->npsn]);
        $tanpaPt = Dpl::factory()->create(['kodept' => null]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn]);
        Mahasiswa::factory()->create(['kodept' => $pt->npsn]);
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);

        foreach (['admmentoring', 'admmentoring/listdata', 'admmentoring/tambah', 'admmentoring/kandidat/'.$dpl->id_dpl, 'admmentoring/kandidat/'.$tanpaPt->id_dpl] as $uri) {
            $text = $this->visibleText($this->get($uri)->assertOk()->getContent());
            $this->assertStringNotContainsStringIgnoringCase('mentoring', $text, $uri);
        }

        $row = $this->getJson(self::LIST, self::AJAX)->assertOk()->json('data.0');
        $this->assertStringNotContainsStringIgnoringCase('mentoring', $this->visibleText($row['action']));
        $this->assertStringContainsString('Batalkan penugasan mahasiswa ini dari DPL?', $row['action']);

        // Empty-state of tambah (no eligible DPL)
        Dpl::query()->update(['kodept' => null]);
        $text = $this->visibleText($this->get('admmentoring/tambah')->assertOk()->getContent());
        $this->assertStringNotContainsStringIgnoringCase('mentoring', $text);
    }

    public function test_insert_rejects_cross_pt_payload_entirely(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        $lain = Mahasiswa::factory()->count(3)->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);

        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $lain->pluck('email')->implode(',')])
            ->assertOk()->assertExactJson(['success' => '0 mahasiswa berhasil ditugaskan']);
        $this->assertDatabaseCount('dpl_mentoring', 0);
    }

    public function test_insert_accepts_exactly_1000_and_rejects_1001(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);
        $emails = array_map(fn ($i) => "m{$i}@pps.test", range(1, 999));

        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => implode(',', [...$emails, $mhs->email])])
            ->assertExactJson(['success' => '1 mahasiswa berhasil ditugaskan']);

        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => implode(',', [...$emails, 'a@pps.test', 'b@pps.test'])])
            ->assertExactJson(['error' => 'maksimal 1000 mahasiswa per simpan']);
        $this->assertDatabaseCount('dpl_mentoring', 1);
    }

    public function test_dpl_created_assignment_listed_and_cancellable_by_admin(): void
    {
        [$user, $dpl] = $this->dplUser();
        $mhs = Mahasiswa::factory()->create(['kodept' => $dpl->kodept, 'nim' => '400000004']);

        $this->actingAs($user);
        $this->put('dplmentoring/insert', ['createuser' => [$mhs->email]])
            ->assertJson(['success' => '1 mahasiswa berhasil ditambahkan']);

        $this->loginAs('admin');
        $row = $this->getJson(self::LIST, self::AJAX)->assertOk()
            ->assertJsonPath('recordsTotal', 1)->json('data.0');
        $this->assertSame(['400000004', $dpl->email], [$row['nim'], $row['email_dpl']]);

        $this->put('admmentoring/destroy', ['id_mentoring' => $row['id_mentoring']])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('dpl_mentoring', 0);

        $this->get('admmentoring/kandidat/'.$dpl->id_dpl)
            ->assertViewHas('data', fn ($data) => $data->pluck('email')->contains($mhs->email));
        $this->actingAs($user);
        $this->get('dplmentoring/rekapnilai/'.base64_encode($mhs->email))->assertNotFound();
    }

    public function test_dpl_can_cancel_admin_created_assignment(): void
    {
        [$user, $dpl] = $this->dplUser();
        $mhs = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);

        $this->loginAs('admin');
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $mhs->email])
            ->assertJson(['success' => '1 mahasiswa berhasil ditugaskan']);
        $relasi = Dplmentoring::where('email_mahasiswa', $mhs->email)->firstOrFail();

        $this->actingAs($user);
        $this->put('dplmentoring/destroy/'.$relasi->id_mentoring)->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('dpl_mentoring', ['id_mentoring' => $relasi->id_mentoring]);

        // DPL can re-claim the student via the unchanged DPL flow
        $this->get('dplmentoring/tambah')->assertOk()
            ->assertViewHas('data', fn ($data) => $data->pluck('email')->contains($mhs->email));
    }

    public function test_admin_cannot_override_dpl_claim_one_dpl_per_student(): void
    {
        [$user, $dpl] = $this->dplUser();
        $dplKedua = Dpl::factory()->create(['kodept' => $dpl->kodept]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);

        $this->actingAs($user);
        $this->put('dplmentoring/insert', ['createuser' => [$mhs->email]]);

        $this->loginAs('admin');
        $this->put('admmentoring/insert', ['id_dpl' => $dplKedua->id_dpl, 'createuser' => $mhs->email])
            ->assertExactJson(['success' => '0 mahasiswa berhasil ditugaskan']);

        $this->assertSame([$dpl->email], Dplmentoring::where('email_mahasiswa', $mhs->email)->pluck('email_dpl')->all());
    }

    public function test_student_profile_shows_admin_assigned_dpl(): void
    {
        $mhsUser = $this->loginAs('mahasiswa');
        $mhs = Mahasiswa::where('email', $mhsUser->email)->firstOrFail();
        $dpl = Dpl::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        $mhs->update(['kodept' => $dpl->kodept]);

        $this->loginAs('admin');
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $mhs->email])
            ->assertJson(['success' => '1 mahasiswa berhasil ditugaskan']);

        $this->actingAs($mhsUser);
        $this->get('mhsprofile/data')->assertOk()
            ->assertViewHas('mentor', fn ($mentor) => $mentor?->is($dpl));
    }

    public function test_listdataserver_paging(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        foreach (Mahasiswa::factory()->count(3)->create(['kodept' => $dpl->kodept]) as $mhs) {
            Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        }

        $page = fn (int $start) => $this->getJson("admmentoring/listdataserver?draw=1&start={$start}&length=2", self::AJAX)->assertOk();
        $page(0)->assertJsonPath('recordsTotal', 3)->assertJsonCount(2, 'data');
        $page(2)->assertJsonCount(1, 'data');
    }

    public function test_mutating_routes_use_web_csrf_and_policy_middleware(): void
    {
        foreach (['admmentoring.insert', 'admmentoring.destroy'] as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('web', $middleware, $name);
            $this->assertContains('can:manage,'.Dplmentoring::class, $middleware, $name);
        }
    }
}
