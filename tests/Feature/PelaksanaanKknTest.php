<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

use App\Models\Desa;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\PelaksanaanKkn;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;

class PelaksanaanKknTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->ptA = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $this->ptB = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Beta']);
    }

    private function loginPt(Satuanpendidikan $pt): User
    {
        return $this->loginAs('pt', ['email' => $pt->npsn]);
    }

    private function simpan(string $mulai, string $selesai, array $extra = []): TestResponse
    {
        return $this->put('pelaksanaankkn/simpan', ['tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai] + $extra);
    }

    private function listJson(): TestResponse
    {
        return $this->getJson('pelaksanaankkn/listdataserver?draw=1&start=0&length=50', self::AJAX);
    }

    private function loginKetua(): User
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    // --- AC1: form di atas dashboard + CRUD ---

    public function test_pt_dashboard_shows_form_above_other_cards(): void
    {
        $this->loginPt($this->ptA);

        $this->get('home')->assertOk()
            ->assertViewHas('pelaksanaanKkn', null)
            ->assertViewHas('bisaIsiPelaksanaan', true)
            ->assertSee('action="'.route('pelaksanaankkn.simpan').'"', false)
            ->assertSeeInOrder(['id="cardPelaksanaanKkn"', '<div class="row g-6">'], false);
    }

    public function test_dashboard_form_prefilled_with_existing_record(): void
    {
        $this->loginPt($this->ptA);
        PelaksanaanKkn::factory()->forPt($this->ptA->npsn)->create(['tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-08-15']);

        $this->get('home')->assertOk()
            ->assertViewHas('pelaksanaanKkn', fn ($p) => $p?->kodept === $this->ptA->npsn && $p->durasi_hari === 46)
            ->assertSee('2026-07-01', false)
            ->assertSee('2026-08-15', false)
            ->assertSee('action="'.route('pelaksanaankkn.destroy').'"', false);
    }

    public function test_other_roles_do_not_see_dashboard_form(): void
    {
        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->get('home')->assertOk()->assertDontSee('id="cardPelaksanaanKkn"', false);
        }
    }

    public function test_pt_can_create_update_and_delete_single_record(): void
    {
        $user = $this->loginPt($this->ptA);
        $year = now()->year;

        $this->simpan("$year-01-01", "$year-01-03")->assertOk()->assertJson(['success' => true]);
        $record = PelaksanaanKkn::where('kodept', $this->ptA->npsn)->firstOrFail();
        $this->assertSame(3, $record->durasi_hari);
        $this->assertSame($user->id, $record->updated_by);

        $this->simpan("$year-02-01", "$year-02-01")->assertJson(['success' => true]);
        $this->assertSame(1, PelaksanaanKkn::count(), 'satu record per PT');
        $record->refresh();
        $this->assertSame("$year-02-01", $record->tanggal_mulai->toDateString());
        $this->assertSame(1, $record->durasi_hari);

        $this->put('pelaksanaankkn/destroy')->assertOk()->assertJson(['success' => true]);
        $this->assertSame(0, PelaksanaanKkn::count());

        $this->put('pelaksanaankkn/destroy')->assertNotFound()->assertJson(['success' => false]);
    }

    public function test_date_validation(): void
    {
        $max = (now()->year + 1).'-12-31';
        $cases = [
            'kosong' => [['', ''], ['tanggal_mulai', 'tanggal_selesai']],
            'bukan date lengkap' => [['2026-07', '2026-08'], ['tanggal_mulai', 'tanggal_selesai']],
            'format salah' => [['01/07/2026', '2026-08-01'], ['tanggal_mulai']],
            'tanggal tidak ada' => [['2026-02-30', '2026-03-01'], ['tanggal_mulai']],
            'selesai < mulai' => [['2026-08-02', '2026-08-01'], ['tanggal_selesai']],
            'durasi 367 hari' => [['2024-01-01', '2025-01-01'], ['tanggal_selesai']],
            'sebelum min' => [['2019-12-31', '2020-01-05'], ['tanggal_mulai']],
            'lewat max' => [[$max, date('Y-m-d', strtotime($max.' +1 day'))], ['tanggal_selesai']],
        ];

        foreach ($cases as $label => [[$mulai, $selesai], $errorKeys]) {
            // Pengguna baru per kasus agar tidak kena throttle:sensitive
            $this->loginPt(Satuanpendidikan::factory()->create());
            $response = $this->simpan($mulai, $selesai)->assertJson(['success' => false]);
            foreach ($errorKeys as $key) {
                $this->assertArrayHasKey($key, $response->json('errors'), $label);
            }
        }
        $this->assertSame(0, PelaksanaanKkn::count());
    }

    public function test_max_duration_366_days_inclusive_is_accepted(): void
    {
        $this->loginPt($this->ptA);

        // 2024 kabisat: 1 Jan - 31 Des = 366 hari inklusif
        $this->simpan('2024-01-01', '2024-12-31')->assertJson(['success' => true]);
        $this->assertSame(366, PelaksanaanKkn::firstOrFail()->durasi_hari);
    }

    public function test_pt_without_satuanpendidikan_cannot_save(): void
    {
        $this->loginAs('pt', ['email' => '9999999999']);

        $this->get('home')->assertOk()->assertViewHas('bisaIsiPelaksanaan', false);
        $this->simpan('2026-07-01', '2026-07-10')->assertForbidden()->assertJson(['success' => false]);
        $this->assertSame(0, PelaksanaanKkn::count());
    }

    // --- AC2: IDOR ---

    public function test_client_kodept_and_id_are_ignored(): void
    {
        $milikB = PelaksanaanKkn::factory()->forPt($this->ptB->npsn)->create(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-10']);
        $userA = $this->loginPt($this->ptA);
        $other = User::factory()->role('admin')->create();

        $this->simpan('2026-05-01', '2026-05-05', [
            'kodept' => $this->ptB->npsn,
            'id_pelaksanaan' => $milikB->id_pelaksanaan,
            'id' => $milikB->id_pelaksanaan,
            'updated_by' => $other->id,
        ])->assertJson(['success' => true]);

        $milikB->refresh();
        $this->assertSame('2026-01-01', $milikB->tanggal_mulai->toDateString(), 'record PT B tidak boleh berubah');
        $milikA = PelaksanaanKkn::where('kodept', $this->ptA->npsn)->firstOrFail();
        $this->assertNotSame($milikB->id_pelaksanaan, $milikA->id_pelaksanaan);
        $this->assertSame($userA->id, $milikA->updated_by);
        $this->assertSame('2026-05-01', $milikA->tanggal_mulai->toDateString());
    }

    public function test_pt_cannot_delete_other_pt_record(): void
    {
        $milikB = PelaksanaanKkn::factory()->forPt($this->ptB->npsn)->create();
        $this->loginPt($this->ptA);

        $this->put('pelaksanaankkn/destroy', [
            'id_pelaksanaan' => $milikB->id_pelaksanaan,
            'kodept' => $this->ptB->npsn,
        ])->assertNotFound();

        $this->assertModelExists($milikB);
    }

    // --- AC3: role selain PT ditolak ---

    public function test_non_pt_roles_cannot_save_or_destroy(): void
    {
        $milikA = PelaksanaanKkn::factory()->forPt($this->ptA->npsn)->create(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-10']);
        $logins = [
            'admin' => fn () => $this->loginAs('admin'),
            'kepala' => fn () => $this->loginAs('kepala'),
            'pemda' => fn () => $this->loginAs('pemda'),
            'dpl' => fn () => $this->loginAs('dpl'),
            'mahasiswa' => fn () => $this->loginAs('mahasiswa'),
            'ketua' => fn () => $this->loginKetua(),
            // Akun non-PT dengan email = NPSN PT A tetap ditolak
            'admin-npsn' => fn () => $this->loginAs('admin', ['email' => $this->ptA->npsn]),
        ];

        foreach ($logins as $label => $login) {
            $login();
            $status = $this->simpan('2026-05-01', '2026-05-05')->status();
            $this->assertTrue(in_array($status, [302, 403], true), "$label simpan: $status");
            $status = $this->put('pelaksanaankkn/destroy')->status();
            $this->assertTrue(in_array($status, [302, 403], true), "$label destroy: $status");
        }

        $this->assertSame(1, PelaksanaanKkn::count());
        $this->assertSame('2026-01-01', $milikA->fresh()->tanggal_mulai->toDateString());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->simpan('2026-05-01', '2026-05-05')->assertRedirect(route('login'));
        $this->put('pelaksanaankkn/destroy')->assertRedirect(route('login'));
        $this->get('pelaksanaankkn')->assertRedirect(route('login'));
    }

    // --- AC4: daftar read-only untuk admin/kepala/pemda ---

    public function test_viewer_roles_can_open_list_and_json_has_no_action_column(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji']);
        Mahasiswa::factory()->create(['kodept' => $this->ptA->npsn, 'location_program' => $lokasi->id]);
        PelaksanaanKkn::factory()->forPt($this->ptA->npsn)->create(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-03']);
        PelaksanaanKkn::factory()->forPt($this->ptB->npsn)->create(['tanggal_mulai' => '2026-02-01', 'tanggal_selesai' => '2026-02-01']);

        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);

            $this->get('pelaksanaankkn')->assertOk()->assertSee(json_encode(route('pelaksanaankkn.listdata')), false);
            $html = $this->get('pelaksanaankkn/listdata')->assertOk()
                ->assertSee(route('pelaksanaankkn.listdataserver'), false)->getContent();
            $this->assertStringNotContainsString('>Aksi</th>', $html, $role);
            $this->assertStringNotContainsString("data: 'action'", $html, $role);

            $response = $this->listJson()->assertOk()->assertJsonPath('recordsTotal', 2);
            $rows = collect($response->json('data'));
            foreach ($rows as $row) {
                $this->assertArrayNotHasKey('action', $row, $role);
                $this->assertEqualsCanonicalizing(
                    ['DT_RowIndex', 'kodept', 'nama_pt', 'lokasi', 'tanggal_mulai', 'tanggal_selesai', 'durasi_hari', 'updated_at'],
                    array_keys($row), $role
                );
            }
            $rowA = $rows->firstWhere('kodept', $this->ptA->npsn);
            $this->assertSame('Universitas Alfa', $rowA['nama_pt']);
            $this->assertSame('Kota Uji', $rowA['lokasi']);
            $this->assertSame('3 hari', $rowA['durasi_hari']);
            $this->assertSame('1 hari', $rows->firstWhere('kodept', $this->ptB->npsn)['durasi_hari']);
            $this->assertSame('-', $rows->firstWhere('kodept', $this->ptB->npsn)['lokasi']);
        }
    }

    public function test_listdataserver_requires_ajax(): void
    {
        $this->loginAs('admin');

        $this->get('pelaksanaankkn/listdataserver')->assertNotFound();
    }

    public function test_listdataserver_orders_by_durasi_and_ignores_lokasi_order(): void
    {
        PelaksanaanKkn::factory()->forPt($this->ptA->npsn)->create(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-10']);
        PelaksanaanKkn::factory()->forPt($this->ptB->npsn)->create(['tanggal_mulai' => '2026-02-01', 'tanggal_selesai' => '2026-02-02']);
        $this->loginAs('admin');

        $cols = '&columns[0][data]=durasi_hari&columns[0][orderable]=true&columns[0][searchable]=true'
            .'&columns[1][data]=lokasi&columns[1][orderable]=true&columns[1][searchable]=true';
        $res = $this->getJson('pelaksanaankkn/listdataserver?draw=1&start=0&length=50'.$cols.'&order[0][column]=0&order[0][dir]=asc', self::AJAX)->assertOk();
        $this->assertSame(['2 hari', '10 hari'], array_column($res->json('data'), 'durasi_hari'));

        $this->getJson('pelaksanaankkn/listdataserver?draw=1&start=0&length=50'.$cols.'&order[0][column]=1&order[0][dir]=asc&search[value]=Kota', self::AJAX)
            ->assertOk();
    }

    public function test_other_roles_cannot_open_list(): void
    {
        $logins = [
            'pt' => fn () => $this->loginPt($this->ptA),
            'dpl' => fn () => $this->loginAs('dpl'),
            'mahasiswa' => fn () => $this->loginAs('mahasiswa'),
            'ketua' => fn () => $this->loginKetua(),
        ];

        foreach ($logins as $role => $login) {
            $login();
            foreach (['pelaksanaankkn', 'pelaksanaankkn/listdata'] as $uri) {
                $status = $this->get($uri)->status();
                $this->assertTrue(in_array($status, [302, 403], true), "$role $uri: $status");
            }
            $status = $this->listJson()->status();
            $this->assertTrue(in_array($status, [302, 403], true), "$role listdataserver: $status");
        }
    }

    public function test_menu_link_only_for_admin_kepala_pemda(): void
    {
        $menuLink = 'href="'.url('pelaksanaankkn').'"';
        $cases = [
            'admin' => [fn () => $this->loginAs('admin'), true],
            'kepala' => [fn () => $this->loginAs('kepala'), true],
            'pemda' => [fn () => $this->loginAs('pemda'), true],
            'pt' => [fn () => $this->loginPt($this->ptA), false],
            'dpl' => [fn () => $this->loginAs('dpl'), false],
            'mahasiswa' => [fn () => $this->loginAs('mahasiswa'), false],
            'ketua' => [fn () => $this->loginKetua(), false],
        ];

        foreach ($cases as $role => [$login, $visible]) {
            $login();
            // Halaman lain yang memuat layout menu untuk semua role
            $html = $this->get('rencanakerja')->assertOk()->getContent();
            $this->assertSame($visible, str_contains($html, $menuLink), "{$role}: menu pelaksanaan");
        }
    }

    public function test_policy_is_auto_discovered_and_deleting_pt_cascades(): void
    {
        $this->assertInstanceOf(\App\Policies\PelaksanaanKknPolicy::class, \Illuminate\Support\Facades\Gate::getPolicyFor(PelaksanaanKkn::class));

        $record = PelaksanaanKkn::factory()->forPt($this->ptA->npsn)->create();
        $this->ptA->delete();
        $this->assertModelMissing($record);
    }
}
