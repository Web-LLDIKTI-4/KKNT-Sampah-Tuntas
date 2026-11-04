<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MentoringTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    // Same keys as the admmentoring/listdata table
    private const COLUMNS = ['DT_RowIndex', 'id_mentoring', 'nim', 'nama', 'nm_lemb', 'prodi', 'nama_dpl', 'email_dpl', 'created_at', 'action'];

    private const ORDERABLE = ['nim', 'nama', 'nm_lemb', 'prodi', 'nama_dpl', 'email_dpl', 'created_at'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function dpl(?string $kodept = null, array $attrs = []): Dpl
    {
        return Dpl::factory()->create($attrs + ['kodept' => $kodept ?? Satuanpendidikan::factory()->create()->npsn]);
    }

    private function assignment(Mahasiswa $mhs, string $emailDpl): Dplmentoring
    {
        return Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $emailDpl]);
    }

    private function listUri(array $extra = []): string
    {
        $params = ['draw' => 1, 'start' => 0, 'length' => 10];
        foreach (self::COLUMNS as $i => $data) {
            $searchable = in_array($data, self::ORDERABLE, true) ? 'true' : 'false';
            $params['columns'][$i] = ['data' => $data, 'name' => $data, 'searchable' => $searchable, 'orderable' => $searchable, 'search' => ['value' => '', 'regex' => 'false']];
        }

        return 'admmentoring/listdataserver?'.http_build_query(array_replace_recursive($params, $extra));
    }

    private function loginNonAdmin(string $role): void
    {
        $attributes = $role === 'pt' ? ['email' => Satuanpendidikan::factory()->create()->npsn] : [];
        $this->loginAs($role, $attributes);
    }

    public static function nonAdminRoles(): array
    {
        return ['mahasiswa' => ['mahasiswa'], 'pt' => ['pt'], 'dpl' => ['dpl'], 'pemda' => ['pemda'], 'kepala' => ['kepala']];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_is_forbidden_on_all_routes(string $role): void
    {
        $dpl = $this->dpl();
        $mhs = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);
        $relasi = $this->assignment(Mahasiswa::factory()->create(), 'dpl-lain@pps.test');
        $this->loginNonAdmin($role);

        foreach (['admmentoring', 'admmentoring/listdata', 'admmentoring/tambah', 'admmentoring/kandidat/'.$dpl->id_dpl] as $uri) {
            $this->get($uri)->assertForbidden();
        }
        $this->getJson($this->listUri(), self::AJAX)->assertForbidden();
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $mhs->email])->assertForbidden();
        $this->put('admmentoring/destroy', ['id_mentoring' => $relasi->id_mentoring])->assertForbidden();

        $this->assertDatabaseCount('dpl_mentoring', 1);
        $this->assertDatabaseHas('dpl_mentoring', ['id_mentoring' => $relasi->id_mentoring]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $dpl = $this->dpl();
        $relasi = $this->assignment(Mahasiswa::factory()->create(), 'dpl-lain@pps.test');

        foreach (['admmentoring', 'admmentoring/listdata', 'admmentoring/tambah', 'admmentoring/kandidat/'.$dpl->id_dpl] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => 'x@pps.test'])->assertRedirect(route('login'));
        $this->put('admmentoring/destroy', ['id_mentoring' => $relasi->id_mentoring])->assertRedirect(route('login'));

        $this->assertDatabaseHas('dpl_mentoring', ['id_mentoring' => $relasi->id_mentoring]);
    }

    public function test_admin_pages_receive_contract_data(): void
    {
        $this->loginAs('admin');
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $layak = $this->dpl($pt->npsn, ['nama' => 'Budi']);
        $this->dpl(null, ['kodept' => null]);
        $this->dpl($pt->npsn, ['email' => '']);

        $this->get('admmentoring')->assertOk();
        $this->get('admmentoring/listdata')->assertOk()
            ->assertViewHas('dpls', fn ($dpls) => $dpls->pluck('id_dpl')->all() === [$layak->id_dpl] && $dpls->first()->sp?->nm_lemb === 'Universitas Alfa')
            ->assertViewHas('pts', fn ($pts) => $pts->pluck('npsn')->all() === [$pt->npsn]);
        $this->get('admmentoring/tambah')->assertOk()
            ->assertViewHas('dpls', fn ($dpls) => $dpls->pluck('id_dpl')->all() === [$layak->id_dpl]);
    }

    public function test_listdataserver_lists_dpl_and_admin_assignments_with_filters(): void
    {
        $this->loginAs('admin');
        $ptA = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $ptB = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Beta']);
        $dplA = $this->dpl($ptA->npsn, ['nama' => 'Dosen Alfa']);
        $dplB = $this->dpl($ptB->npsn, ['nama' => 'Dosen Beta']);
        $mhsA = Mahasiswa::factory()->create(['kodept' => $ptA->npsn, 'nim' => '100000001', 'nama' => 'Ani']);
        $mhsB = Mahasiswa::factory()->create(['kodept' => $ptB->npsn, 'nim' => '200000002', 'nama' => 'Budi']);
        // Created by DPL (direct row) and by admin (insert route)
        $this->assignment($mhsA, $dplA->email);
        $this->put('admmentoring/insert', ['id_dpl' => $dplB->id_dpl, 'createuser' => $mhsB->email])
            ->assertJson(['success' => '1 mahasiswa berhasil ditugaskan']);

        $this->getJson($this->listUri(), self::AJAX)->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonCount(2, 'data');
        $row = $this->getJson($this->listUri(['search' => ['value' => 'Ani']]), self::AJAX)
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->json('data.0');
        $this->assertSame(['100000001', 'Ani', 'Universitas Alfa', 'Dosen Alfa', $dplA->email], [$row['nim'], $row['nama'], $row['nm_lemb'], $row['nama_dpl'], $row['email_dpl']]);
        $this->assertMatchesRegularExpression('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}$/', $row['created_at']);
        $this->assertStringContainsString(route('admmentoring.destroy'), $row['action']);
        $this->assertSame(self::COLUMNS, array_keys($row));

        $this->getJson($this->listUri(['search' => ['value' => 'Dosen Beta']]), self::AJAX)
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nim', '200000002');
        $this->getJson($this->listUri(['email_dpl' => $dplB->email]), self::AJAX)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nim', '200000002');
        $this->getJson($this->listUri(['kodept' => $ptA->npsn]), self::AJAX)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nim', '100000001');
        // Non-string / empty filters ignored
        $this->getJson($this->listUri(['email_dpl' => ['x'], 'kodept' => '']), self::AJAX)->assertJsonCount(2, 'data');
        $this->get($this->listUri())->assertNotFound();
    }

    public function test_listdataserver_orders_every_orderable_column(): void
    {
        $this->loginAs('admin');
        $pt = Satuanpendidikan::factory()->create();
        foreach (['Ani', 'Budi'] as $i => $nama) {
            $dpl = $this->dpl($pt->npsn, ['nama' => 'Dosen '.$nama]);
            $this->assignment(Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => $nama, 'nim' => '10000000'.$i]), $dpl->email);
        }
        // Mentee without mahasiswa/DPL record stays listed
        $this->assignment(Mahasiswa::factory()->make(['email' => 'hantu@pps.test']), 'dpl-hantu@pps.test');

        foreach (self::ORDERABLE as $column) {
            $index = array_search($column, self::COLUMNS, true);
            foreach (['asc', 'desc'] as $dir) {
                $this->getJson($this->listUri(['order' => [['column' => $index, 'dir' => $dir]]]), self::AJAX)
                    ->assertOk()->assertJsonMissingPath('error')->assertJsonCount(3, 'data');
            }
        }
        $names = array_column($this->getJson($this->listUri(['order' => [['column' => 3, 'dir' => 'asc']]]), self::AJAX)->json('data'), 'nama');
        $this->assertSame(['Ani', 'Budi'], array_values(array_diff($names, ['tidak ada'])));
    }

    public function test_kandidat_only_same_pt_and_unassigned(): void
    {
        $this->loginAs('admin');
        $dpl = $this->dpl();
        $bebas = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);
        $sudahAda = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);
        $this->assignment($sudahAda, 'dpl-lain@pps.test');
        Mahasiswa::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);

        $this->get('admmentoring/kandidat/'.$dpl->id_dpl)->assertOk()
            ->assertViewHas('kodeptMissing', false)
            ->assertViewHas('dpl', fn ($d) => $d->is($dpl))
            ->assertViewHas('data', fn ($data) => $data->pluck('email')->all() === [$bebas->email]);

        $tanpaPt = $this->dpl(null, ['kodept' => null]);
        $this->get('admmentoring/kandidat/'.$tanpaPt->id_dpl)->assertOk()
            ->assertViewHas('kodeptMissing', true)
            ->assertViewHas('data', fn ($data) => $data->isEmpty());

        $this->get('admmentoring/kandidat/'.Str::uuid())->assertNotFound();
        $this->get('admmentoring/kandidat/bukan-uuid')->assertNotFound();
    }

    public function test_insert_counts_only_eligible_and_keeps_existing_assignment(): void
    {
        $this->loginAs('admin');
        $dpl = $this->dpl();
        $bebas = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);
        $sudahAda = Mahasiswa::factory()->create(['kodept' => $dpl->kodept]);
        $kampusLain = Mahasiswa::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        $lama = $this->assignment($sudahAda, 'dpl-lain@pps.test');

        $this->put('admmentoring/insert', [
            'id_dpl' => $dpl->id_dpl,
            'createuser' => implode(',', [$bebas->email, $sudahAda->email, $kampusLain->email, 'hantu@pps.test']),
        ])->assertOk()->assertExactJson(['success' => '1 mahasiswa berhasil ditugaskan']);

        $this->assertDatabaseHas('dpl_mentoring', ['email_mahasiswa' => $bebas->email, 'email_dpl' => $dpl->email]);
        $this->assertDatabaseHas('dpl_mentoring', ['id_mentoring' => $lama->id_mentoring, 'email_dpl' => 'dpl-lain@pps.test']);
        $this->assertDatabaseMissing('dpl_mentoring', ['email_mahasiswa' => $kampusLain->email]);
        $this->assertDatabaseCount('dpl_mentoring', 2);

        // Re-submit: nothing new stored
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $bebas->email])
            ->assertExactJson(['success' => '0 mahasiswa berhasil ditugaskan']);
    }

    public function test_insert_rejects_invalid_payloads(): void
    {
        $this->loginAs('admin');
        $dpl = $this->dpl();
        $tanpaPt = $this->dpl(null, ['kodept' => null]);
        $tanpaEmail = $this->dpl(null, ['email' => '']);
        $emails = implode(',', array_map(fn ($i) => "m{$i}@pps.test", range(1, 1001)));

        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $emails])
            ->assertExactJson(['error' => 'maksimal 1000 mahasiswa per simpan']);
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl])->assertExactJson(['error' => 'user harus dipilih']);
        foreach ([null, 'bukan-uuid', (string) Str::uuid(), ['x']] as $id) {
            $this->put('admmentoring/insert', ['id_dpl' => $id, 'createuser' => 'x@pps.test'])
                ->assertExactJson(['error' => 'DPL harus dipilih']);
        }
        foreach ([$tanpaPt, $tanpaEmail] as $row) {
            $this->put('admmentoring/insert', ['id_dpl' => $row->id_dpl, 'createuser' => 'x@pps.test'])
                ->assertExactJson(['error' => 'DPL belum memiliki perguruan tinggi di Data DPL']);
        }
        $this->assertDatabaseCount('dpl_mentoring', 0);
    }

    public function test_dpl_gains_and_loses_access_after_admin_assign_and_destroy(): void
    {
        $dplUser = User::factory()->role('dpl')->create();
        $dpl = $this->dpl(null, ['email' => $dplUser->email]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $dpl->kodept, 'nim' => '300000003']);
        $rekap = 'dplmentoring/rekapnilai/'.base64_encode($mhs->email);

        $this->loginAs('admin');
        $this->put('admmentoring/insert', ['id_dpl' => $dpl->id_dpl, 'createuser' => $mhs->email])
            ->assertJson(['success' => '1 mahasiswa berhasil ditugaskan']);
        $relasi = Dplmentoring::where('email_mahasiswa', $mhs->email)->firstOrFail();

        $this->actingAs($dplUser);
        $this->getJson('dplmentoring/listdataserver?draw=1&start=0&length=10', self::AJAX)
            ->assertOk()->assertJsonPath('data.0.nim', '300000003');
        $this->get($rekap)->assertOk();

        $this->loginAs('admin');
        $this->put('admmentoring/destroy', ['id_mentoring' => $relasi->id_mentoring])
            ->assertOk()->assertExactJson(['success' => true, 'message' => 'Penugasan berhasil dibatalkan']);
        $this->assertDatabaseMissing('dpl_mentoring', ['id_mentoring' => $relasi->id_mentoring]);
        $this->get('admmentoring/kandidat/'.$dpl->id_dpl)
            ->assertViewHas('data', fn ($data) => $data->pluck('email')->contains($mhs->email));

        $this->actingAs($dplUser);
        $this->get($rekap)->assertNotFound();
        $this->getJson('dplmentoring/listdataserver?draw=1&start=0&length=10', self::AJAX)->assertJsonCount(0, 'data');
    }

    public function test_destroy_unknown_or_invalid_id_returns_not_found(): void
    {
        $this->loginAs('admin');
        $relasi = $this->assignment(Mahasiswa::factory()->create(), 'dpl-lain@pps.test');

        foreach ([(string) Str::uuid(), 'bukan-uuid', null, [$relasi->id_mentoring]] as $id) {
            $this->put('admmentoring/destroy', ['id_mentoring' => $id])
                ->assertNotFound()->assertExactJson(['success' => false, 'message' => 'Data tidak ditemukan!']);
        }
        $this->assertDatabaseHas('dpl_mentoring', ['id_mentoring' => $relasi->id_mentoring]);
    }
}
