<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PtpjdesaTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    private Pjdesa $ownA1;

    private Pjdesa $ownA2;

    private Pjdesa $otherB;

    private Mahasiswa $ketuaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ptA = Satuanpendidikan::factory()->create();
        $this->ptB = Satuanpendidikan::factory()->create();

        $this->ownA1 = $this->kelompok($this->ptA, 'Ketua Alfa');
        $this->ownA2 = $this->kelompok($this->ptA, 'Ketua Beta');
        $this->otherB = $this->kelompok($this->ptB, 'Ketua Rahasia');
        $this->ketuaB = Mahasiswa::where('email', $this->otherB->email)->first();
    }

    private function kelompok(Satuanpendidikan $pt, string $nama): Pjdesa
    {
        $mahasiswa = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => $nama]);
        $pjdesa = Pjdesa::create(['email' => $mahasiswa->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $pjdesa->refresh();
    }

    private function loginPtA(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);
    }

    private function list(string $prefix = 'ptpjdesa', array $params = [])
    {
        $columns = [];
        foreach (['DT_RowIndex', 'kecamatan', 'desa', 'pjdesa', 'mobilisasi', 'action'] as $i => $name) {
            $searchable = ! in_array($name, ['DT_RowIndex', 'mobilisasi', 'action'], true);
            $columns[$i] = [
                'data' => $name, 'name' => $name,
                'searchable' => $searchable ? 'true' : 'false',
                'orderable' => in_array($name, ['DT_RowIndex', 'action'], true) ? 'false' : 'true',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }
        $query = http_build_query($params + [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => $columns,
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        return $this->getJson($prefix.'/listdataserver?'.$query, self::AJAX);
    }

    private function update(array $payload)
    {
        return $this->putJson('ptpjdesa/update', $payload, self::AJAX);
    }

    // AC: menu Ketua Kelompok tampil untuk PT
    public function test_pt_sees_ketua_kelompok_menu_and_page(): void
    {
        $this->loginPtA();

        $this->get('ptpjdesa')->assertOk()
            ->assertSee(url('ptpjdesa'), false)
            ->assertSee('Ketua Kelompok');
    }

    // AC: role lain tidak mendapat menu PT ini
    public function test_other_roles_do_not_get_pt_menu(): void
    {
        foreach (['kepala', 'pemda', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $html = view('layouts.menu')->render();
            $this->assertStringNotContainsString(url('ptpjdesa'), $html, $role);
        }
    }

    // AC: kolom daftar PT
    public function test_pt_listdata_has_required_columns(): void
    {
        $this->loginPtA();

        $this->get('ptpjdesa/listdata')->assertOk()
            ->assertSeeInOrder(['Kecamatan', 'Kelurahan/Desa', 'Ketua Kelompok', 'Status Mobilisasi', 'Aksi']);
    }

    // AC: scoping PT + default Belum + tanpa id/email
    public function test_pt_listdataserver_only_contains_own_kelompok(): void
    {
        // Mode debug DataTables menambah key "queries" berisi binding email; uji perilaku production
        config(['app.debug' => false]);
        $this->loginPtA();

        $res = $this->list()->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 2);

        $rows = $res->json('data');
        $this->assertEqualsCanonicalizing(['Ketua Alfa', 'Ketua Beta'], array_column($rows, 'pjdesa'));
        $this->assertSame(['DT_RowIndex', 'kecamatan', 'desa', 'pjdesa', 'mobilisasi', 'action'], array_keys($rows[0]));
        foreach ($rows as $row) {
            $this->assertStringContainsString('Belum', $row['mobilisasi']);
            $this->assertStringNotContainsString('pjdesa/destroy', $row['action']);
        }
        $this->assertStringNotContainsString($this->otherB->email, $res->getContent());
        $this->assertStringNotContainsString($this->ownA1->email, $res->getContent());
    }

    // AC: tidak bocor lewat search/sort/paging
    public function test_pt_scope_holds_on_search_sort_and_paging(): void
    {
        $this->loginPtA();

        $this->list('ptpjdesa', ['search' => ['value' => 'Rahasia', 'regex' => 'false']])
            ->assertOk()->assertJsonPath('recordsFiltered', 0)->assertJsonCount(0, 'data');

        $sorted = $this->list('ptpjdesa', ['order' => [['column' => 3, 'dir' => 'desc']]])->assertOk();
        $this->assertSame(['Ketua Beta', 'Ketua Alfa'], array_column($sorted->json('data'), 'pjdesa'));

        $this->list('ptpjdesa', ['order' => [['column' => 4, 'dir' => 'asc']]])
            ->assertOk()->assertJsonPath('recordsTotal', 2);

        $page2 = $this->list('ptpjdesa', ['start' => 1, 'length' => 1, 'order' => [['column' => 3, 'dir' => 'asc']]])->assertOk();
        $this->assertSame(['Ketua Beta'], array_column($page2->json('data'), 'pjdesa'));
        $this->list('ptpjdesa', ['start' => 2, 'length' => 1])->assertOk()->assertJsonCount(0, 'data');
    }

    // AC: PT tanpa kelompok -> tabel kosong
    public function test_pt_without_kelompok_gets_empty_table(): void
    {
        $this->loginAs('pt', ['email' => Satuanpendidikan::factory()->create()->npsn]);

        $this->get('ptpjdesa')->assertOk();
        $this->get('ptpjdesa/listdata')->assertOk();
        $this->list()->assertOk()->assertJsonPath('recordsTotal', 0)->assertJsonCount(0, 'data');
    }

    public function test_listdataserver_rejects_non_ajax(): void
    {
        $this->loginPtA();

        $this->get('ptpjdesa/listdataserver')->assertNotFound();
    }

    // AC: form edit, pertanyaan & 2 pilihan terisi sesuai status
    public function test_pt_edit_form_shows_question_and_current_status(): void
    {
        $this->ownA1->update(['status_mobilisasi' => 'sudah']);
        $this->loginPtA();

        $html = $this->get('ptpjdesa/edit/'.$this->ownA1->id_pjdesa)->assertOk()
            ->assertSee('Apakah kelompok ini sudah mobilisasi?')
            ->assertSee('Ketua Alfa')
            ->getContent();

        $this->assertSame(2, preg_match_all('/name="status_mobilisasi"/', $html));
        $this->assertMatchesRegularExpression('/value="sudah"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="belum"[^>]*checked/', $html);

        $html = $this->get('ptpjdesa/edit/'.$this->ownA2->id_pjdesa)->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/value="belum"[^>]*checked/', $html);
    }

    public function test_pt_edit_of_other_pt_or_unknown_id_is_404(): void
    {
        $this->loginPtA();

        $this->get('ptpjdesa/edit/'.$this->otherB->id_pjdesa)->assertNotFound();
        $this->get('ptpjdesa/edit/'.Str::uuid())->assertNotFound();
        $this->get('ptpjdesa/edit/not-a-uuid')->assertNotFound();
    }

    // AC: simpan mengubah status, hanya status_mobilisasi, list langsung ter-update
    public function test_pt_updates_own_kelompok_status(): void
    {
        $this->loginPtA();
        $before = $this->ownA1->only(['email', 'id_desa']);

        $this->update([
            'id_pjdesa' => $this->ownA1->id_pjdesa,
            'status_mobilisasi' => 'sudah',
            'email' => $this->ketuaB->email,
            'id_desa' => $this->otherB->id_desa,
        ])->assertOk()->assertJson(['success' => true])->assertJsonStructure(['success', 'message']);

        $fresh = $this->ownA1->fresh();
        $this->assertSame('sudah', $fresh->status_mobilisasi);
        $this->assertSame($before, $fresh->only(['email', 'id_desa']));
        $this->assertSame('belum', $this->ownA2->fresh()->status_mobilisasi);

        $row = collect($this->list()->json('data'))->firstWhere('pjdesa', 'Ketua Alfa');
        $this->assertStringContainsString('Sudah', $row['mobilisasi']);

        // Bolak-balik diperbolehkan
        $this->update(['id_pjdesa' => $this->ownA1->id_pjdesa, 'status_mobilisasi' => 'belum'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame('belum', $this->ownA1->fresh()->status_mobilisasi);
    }

    // AC: PT tidak bisa mengubah kelompok PT lain
    public function test_pt_cannot_update_other_pt_kelompok_or_unknown_id(): void
    {
        $this->loginPtA();

        $this->update(['id_pjdesa' => $this->otherB->id_pjdesa, 'status_mobilisasi' => 'sudah'])
            ->assertNotFound()->assertJson(['success' => false]);
        $this->assertSame('belum', $this->otherB->fresh()->status_mobilisasi);

        $this->update(['id_pjdesa' => (string) Str::uuid(), 'status_mobilisasi' => 'sudah'])
            ->assertNotFound()->assertJson(['success' => false]);
    }

    // AC: nilai invalid / kosong ditolak, status tidak berubah
    // AjaxFormRequest mengikuti konvensi project: HTTP 200 {success:false, errors} (dibaca crud.js)
    public function test_invalid_status_is_rejected_with_validation_error(): void
    {
        $this->loginPtA();

        foreach (['kosong' => '', 'lain' => 'mungkin', 'kapital' => 'SUDAH', 'array' => ['sudah']] as $label => $value) {
            $res = $this->update(['id_pjdesa' => $this->ownA1->id_pjdesa, 'status_mobilisasi' => $value]);
            $this->assertFalse($res->json('success'), $label);
            $this->assertArrayHasKey('status_mobilisasi', (array) $res->json('errors'), $label);
        }

        $this->update(['id_pjdesa' => $this->ownA1->id_pjdesa])
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.status_mobilisasi.0', 'Pilih status mobilisasi.');

        $this->assertSame('belum', $this->ownA1->fresh()->status_mobilisasi);
    }

    public function test_invalid_id_is_rejected(): void
    {
        $this->loginPtA();

        $res = $this->update(['id_pjdesa' => 'bukan-uuid', 'status_mobilisasi' => 'sudah']);
        $this->assertArrayHasKey('id_pjdesa', (array) $res->json('errors'));
        $this->assertFalse($res->json('success'));
    }

    // AC: role non-PT ditolak di semua route ptpjdesa
    public function test_non_pt_roles_are_rejected(): void
    {
        foreach (['admin', 'kepala', 'pemda', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);

            $this->get('ptpjdesa')->assertRedirect(route('home'));
            $this->get('ptpjdesa/listdata')->assertRedirect(route('home'));
            $this->get('ptpjdesa/listdataserver', self::AJAX)->assertRedirect(route('home'));
            $this->get('ptpjdesa/edit/'.$this->ownA1->id_pjdesa)->assertRedirect(route('home'));
            // kepala/pemda diblok lebih dulu oleh KepalaReadOnly (403)
            $status = $this->put('ptpjdesa/update', ['id_pjdesa' => $this->ownA1->id_pjdesa, 'status_mobilisasi' => 'sudah'], self::AJAX)
                ->getStatusCode();
            $this->assertContains($status, [302, 403], $role);
        }

        $this->assertSame('belum', $this->ownA1->fresh()->status_mobilisasi);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('ptpjdesa')->assertRedirect(route('login'));
        $this->put('ptpjdesa/update', ['id_pjdesa' => $this->ownA1->id_pjdesa, 'status_mobilisasi' => 'sudah'])
            ->assertRedirect(route('login'));
        $this->assertSame('belum', $this->ownA1->fresh()->status_mobilisasi);
    }

    // AC: PT tidak bisa hapus/cabut akses (khusus admin)
    public function test_pt_cannot_access_admin_pjdesa_or_destroy(): void
    {
        $this->loginPtA();

        $this->get('pjdesa')->assertRedirect(route('home'));
        $this->put('pjdesa/destroy', ['id_pjdesa' => $this->ownA1->id_pjdesa], self::AJAX)->assertRedirect(route('home'));
        $this->assertNotNull($this->ownA1->fresh());
    }

    // AC: menu admin tetap berjalan + kolom mobilisasi
    public function test_admin_pjdesa_still_works_with_mobilisasi_column(): void
    {
        $this->loginAs('admin');

        $this->get('pjdesa')->assertOk();
        $this->get('pjdesa/listdata')->assertOk();

        $rows = $this->getJson('pjdesa/listdataserver?draw=1&start=0&length=10', self::AJAX)->assertOk()
            ->assertJsonPath('recordsTotal', 3)
            ->json('data');
        foreach (['kecamatan', 'desa', 'pjdesa', 'instansi', 'capaian_kpi', 'mobilisasi', 'action'] as $key) {
            $this->assertArrayHasKey($key, $rows[0], $key);
        }
        $this->assertStringContainsString('Belum', $rows[0]['mobilisasi']);
        $this->assertStringContainsString('pjdesa/destroy', $rows[0]['action']);

        $this->put('pjdesa/destroy', ['id_pjdesa' => $this->ownA1->id_pjdesa], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->assertNull($this->ownA1->fresh());
    }

    // AC: admin tidak bisa ubah status via route PT
    public function test_policy_denies_admin_update_mobilisasi(): void
    {
        $this->loginAs('admin');

        $this->assertFalse(auth()->user()->can('updateMobilisasi', $this->ownA1));
    }

    public function test_status_mobilisasi_defaults_to_belum(): void
    {
        $this->assertSame('belum', $this->ownA1->status_mobilisasi);
        $this->assertSame(['belum', 'sudah'], Pjdesa::MOBILISASI_STATUSES);
    }

    private function filteredNames(string $prefix, mixed $mobilisasi): array
    {
        $res = $this->list($prefix, ['mobilisasi' => $mobilisasi])->assertOk();
        $names = array_column($res->json('data'), 'pjdesa');
        $this->assertSame(count($names), $res->json('recordsFiltered'), $prefix.':'.json_encode($mobilisasi));

        return $names;
    }

    // AC: filter status mobilisasi tersedia di view PT & admin
    public function test_listdata_views_have_mobilisasi_filter(): void
    {
        $this->loginPtA();
        $this->get('ptpjdesa/listdata')->assertOk()
            ->assertSee('id="filter-mobilisasi-pt"', false)
            ->assertSee('value="sudah"', false)
            ->assertSee('value="belum"', false);

        $this->loginAs('admin');
        $this->get('pjdesa/listdata')->assertOk()
            ->assertSee('id="filter-mobilisasi"', false)
            ->assertSee('value="sudah"', false)
            ->assertSee('value="belum"', false);
    }

    // AC: filter PT sudah/belum, tetap dibatasi kelompok PT sendiri
    public function test_pt_filter_mobilisasi_respects_scope(): void
    {
        $this->ownA1->update(['status_mobilisasi' => 'sudah']);
        $this->otherB->update(['status_mobilisasi' => 'sudah']);
        $this->loginPtA();

        $this->assertSame(['Ketua Alfa'], $this->filteredNames('ptpjdesa', 'sudah'));
        $this->assertSame(['Ketua Beta'], $this->filteredNames('ptpjdesa', 'belum'));

        $res = $this->list('ptpjdesa', ['mobilisasi' => 'sudah'])->assertOk();
        $this->assertStringContainsString('Sudah', $res->json('data.0.mobilisasi'));
        $this->assertStringNotContainsString('Ketua Rahasia', $res->getContent());
    }

    // AC: nilai kosong/invalid diabaikan (PT)
    public function test_pt_filter_mobilisasi_ignores_invalid_values(): void
    {
        $this->ownA1->update(['status_mobilisasi' => 'sudah']);
        $this->otherB->update(['status_mobilisasi' => 'sudah']);
        $this->loginPtA();

        foreach (['', 'x', 'SUDAH', "sudah' OR 1=1", "belum' OR '1'='1", ['sudah']] as $value) {
            $this->assertEqualsCanonicalizing(['Ketua Alfa', 'Ketua Beta'], $this->filteredNames('ptpjdesa', $value), json_encode($value));
        }
    }

    // AC: filter admin sudah/belum/kosong/invalid
    public function test_admin_filter_mobilisasi(): void
    {
        $this->ownA1->update(['status_mobilisasi' => 'sudah']);
        $this->otherB->update(['status_mobilisasi' => 'sudah']);
        $this->loginAs('admin');

        $this->assertEqualsCanonicalizing(['Ketua Alfa', 'Ketua Rahasia'], $this->filteredNames('pjdesa', 'sudah'));
        $this->assertSame(['Ketua Beta'], $this->filteredNames('pjdesa', 'belum'));

        foreach (['', 'x', "sudah' OR 1=1", ['belum']] as $value) {
            $this->assertEqualsCanonicalizing(['Ketua Alfa', 'Ketua Beta', 'Ketua Rahasia'], $this->filteredNames('pjdesa', $value), json_encode($value));
        }
    }

    // AC: role lain tidak bisa memakai filter PT
    public function test_non_pt_cannot_use_pt_filter(): void
    {
        $this->loginAs('mahasiswa');
        $this->get('ptpjdesa/listdataserver?mobilisasi=sudah', self::AJAX)->assertRedirect(route('home'));
    }
}
