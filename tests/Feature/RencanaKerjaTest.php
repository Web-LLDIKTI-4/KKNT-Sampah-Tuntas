<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

use App\Models\Desa;
use App\Models\Pjdesa;
use App\Models\RencanaKerja;
use App\Models\Satuanpendidikan;
use App\Models\User;

class RencanaKerjaTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RencanaKerja::DISK);
        $this->ptA = Satuanpendidikan::factory()->create();
        $this->ptB = Satuanpendidikan::factory()->create();
    }

    private function loginPt(Satuanpendidikan $pt): User
    {
        return $this->loginAs('pt', ['email' => $pt->npsn]);
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'judul' => 'Rencana Kerja Semester Ganjil',
            'tahun' => (string) now()->year,
            'keterangan' => 'Program pengelolaan sampah desa.',
            'file' => UploadedFile::fake()->create('rencana.pdf', 120, 'application/pdf'),
        ];
    }

    private function createWithFile(Satuanpendidikan $pt, array $attributes = []): RencanaKerja
    {
        $filePath = UploadedFile::fake()->create('lama.pdf', 50, 'application/pdf')->store('rencana-kerja', RencanaKerja::DISK);

        return RencanaKerja::factory()->forPt($pt->npsn)->create($attributes + ['file_path' => $filePath, 'nama_file' => 'lama.pdf']);
    }

    // Fake File menebak mime dari nama; file asli dicek finfo dari isinya
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'rk');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function listJson(): TestResponse
    {
        return $this->getJson('rencanakerja/listdataserver?draw=1&start=0&length=50', self::AJAX);
    }

    // --- PT: CRUD miliknya ---

    public function test_pt_can_open_pages_and_insert_own_rencana_kerja(): void
    {
        $user = $this->loginPt($this->ptA);

        $this->get('rencanakerja')->assertOk()->assertSee(route('rencanakerja.tambah'), false);
        $this->get('rencanakerja/listdata')->assertOk()->assertSee(route('rencanakerja.listdataserver'), false);
        $this->get('rencanakerja/tambah')->assertOk()->assertSee('enctype="multipart/form-data"', false);

        $this->put('rencanakerja/insert', $this->validPayload())->assertJson(['success' => true]);

        $rencanaKerja = RencanaKerja::where('judul', 'Rencana Kerja Semester Ganjil')->firstOrFail();
        Storage::disk(RencanaKerja::DISK)->assertExists($rencanaKerja->file_path);
        $this->assertStringStartsWith('rencana-kerja/', $rencanaKerja->file_path);
        $this->assertSame($this->ptA->npsn, $rencanaKerja->kodept);
        $this->assertSame($user->id, $rencanaKerja->uploaded_by);
        $this->assertSame('rencana.pdf', $rencanaKerja->nama_file);
    }

    public function test_pt_cannot_set_kodept_or_uploaded_by_via_payload(): void
    {
        $user = $this->loginPt($this->ptA);
        $other = User::factory()->role('admin')->create();

        $this->put('rencanakerja/insert', $this->validPayload([
            'kodept' => $this->ptB->npsn,
            'uploaded_by' => $other->id,
        ]))->assertJson(['success' => true]);

        $this->assertDatabaseHas('rencana_kerja', ['kodept' => $this->ptA->npsn, 'uploaded_by' => $user->id]);
        $this->assertDatabaseMissing('rencana_kerja', ['kodept' => $this->ptB->npsn]);

        $rencanaKerja = RencanaKerja::firstOrFail();
        $this->put('rencanakerja/update', [
            'id_rencana_kerja' => $rencanaKerja->id_rencana_kerja,
            'judul' => 'Ubah',
            'tahun' => (string) now()->year,
            'kodept' => $this->ptB->npsn,
        ])->assertJson(['success' => true]);

        $this->assertSame($this->ptA->npsn, $rencanaKerja->fresh()->kodept);
    }

    public function test_pt_can_update_own_and_new_file_replaces_old(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = $this->createWithFile($this->ptA);
        $oldFilePath = $rencanaKerja->file_path;

        $this->get('rencanakerja/edit/'.$rencanaKerja->id_rencana_kerja)->assertOk()
            ->assertSee($rencanaKerja->id_rencana_kerja);

        $this->put('rencanakerja/update', $this->validPayload([
            'id_rencana_kerja' => $rencanaKerja->id_rencana_kerja,
            'judul' => 'Rencana Baru',
            'file' => UploadedFile::fake()->create('baru.pdf', 30, 'application/pdf'),
        ]))->assertJson(['success' => true]);

        $rencanaKerja->refresh();
        Storage::disk(RencanaKerja::DISK)->assertMissing($oldFilePath);
        Storage::disk(RencanaKerja::DISK)->assertExists($rencanaKerja->file_path);
        $this->assertSame('baru.pdf', $rencanaKerja->nama_file);
        $this->assertSame('Rencana Baru', $rencanaKerja->judul);
    }

    public function test_update_without_file_keeps_existing_file(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = $this->createWithFile($this->ptA);

        $this->put('rencanakerja/update', $this->validPayload([
            'id_rencana_kerja' => $rencanaKerja->id_rencana_kerja,
            'judul' => 'Judul Saja',
            'file' => null,
        ]))->assertJson(['success' => true]);

        Storage::disk(RencanaKerja::DISK)->assertExists($rencanaKerja->file_path);
        $this->assertSame($rencanaKerja->file_path, $rencanaKerja->fresh()->file_path);
        $this->assertSame('Judul Saja', $rencanaKerja->fresh()->judul);
    }

    public function test_pt_can_delete_own_and_file_is_removed(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = $this->createWithFile($this->ptA);

        $this->put('rencanakerja/destroy', ['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja])
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('rencana_kerja', ['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja]);
        Storage::disk(RencanaKerja::DISK)->assertMissing($rencanaKerja->file_path);
    }

    public function test_pt_can_view_own_pdf_inline(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = $this->createWithFile($this->ptA, ['nama_file' => 'rencana-2026.pdf']);

        $response = $this->get('rencanakerja/view/'.$rencanaKerja->id_rencana_kerja)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('rencana-2026.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_download_route_no_longer_exists(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = $this->createWithFile($this->ptA);

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('rencanakerja.download'));
        $this->get('rencanakerja/download/'.$rencanaKerja->id_rencana_kerja)->assertNotFound();
        $this->listJson()->assertOk()->assertJsonPath('data.0.file', fn (string $cell) => str_contains($cell, route('rencanakerja.view', $rencanaKerja->id_rencana_kerja))
            && str_contains($cell, 'target="_blank"') && ! str_contains($cell, 'download'));
    }

    // --- IDOR antar PT ---

    public function test_pt_list_only_contains_own_rows_without_file_path(): void
    {
        $this->createWithFile($this->ptA, ['judul' => 'Milik A']);
        $this->createWithFile($this->ptB, ['judul' => 'Milik B']);
        $this->loginPt($this->ptA);

        $this->get('rencanakerja/listdata')->assertOk()->assertDontSee("{data: 'pt'", false);

        $response = $this->listJson()->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissingPath('data.0.kodept');
        $this->assertStringContainsString('Milik A', $response->json('data.0.judul'));
        $this->assertStringNotContainsString('Milik B', $response->getContent());
    }

    public function test_pt_cannot_edit_update_delete_or_view_other_pt(): void
    {
        $milikB = $this->createWithFile($this->ptB, ['judul' => 'Milik B']);
        $this->loginPt($this->ptA);

        $this->get('rencanakerja/edit/'.$milikB->id_rencana_kerja)->assertNotFound();
        $this->get('rencanakerja/view/'.$milikB->id_rencana_kerja)->assertNotFound();

        $this->put('rencanakerja/update', $this->validPayload([
            'id_rencana_kerja' => $milikB->id_rencana_kerja,
            'judul' => 'Dibajak',
        ]))->assertNotFound()->assertJson(['success' => false]);

        $this->put('rencanakerja/destroy', ['id_rencana_kerja' => $milikB->id_rencana_kerja])
            ->assertNotFound()->assertJson(['success' => false]);

        $this->assertSame('Milik B', $milikB->fresh()->judul);
        Storage::disk(RencanaKerja::DISK)->assertExists($milikB->file_path);
    }

    public function test_invalid_ids_are_rejected(): void
    {
        $this->loginPt($this->ptA);

        $this->get('rencanakerja/edit/bukan-uuid')->assertNotFound();
        $this->get('rencanakerja/view/1')->assertNotFound();
        $this->put('rencanakerja/destroy', ['id_rencana_kerja' => 'bukan-uuid'])->assertNotFound();
        $this->put('rencanakerja/destroy', ['id_rencana_kerja' => ['array']])->assertNotFound();
        $this->put('rencanakerja/update', $this->validPayload(['id_rencana_kerja' => 'x']))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('id_rencana_kerja', 'errors');
    }

    public function test_pt_without_satuanpendidikan_cannot_create(): void
    {
        $this->loginAs('pt', ['email' => '99999999']);

        $this->get('rencanakerja')->assertOk()->assertDontSee(route('rencanakerja.tambah'), false);
        $this->get('rencanakerja/tambah')->assertForbidden();
        $this->put('rencanakerja/insert', $this->validPayload())->assertForbidden();

        $this->assertDatabaseCount('rencana_kerja', 0);
    }

    // --- Admin ---

    public function test_admin_sees_all_with_pt_column_and_action_buttons(): void
    {
        $this->createWithFile($this->ptA);
        $this->createWithFile($this->ptB);
        $this->loginAs('admin');

        $this->get('rencanakerja')->assertOk()->assertDontSee(route('rencanakerja.tambah'), false);

        $response = $this->listJson()->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonMissingPath('data.0.file_path');
        $names = collect($response->json('data'))->pluck('pt')->all();
        $this->assertEqualsCanonicalizing([e($this->ptA->nm_lemb), e($this->ptB->nm_lemb)], $names);
        $this->assertNotEmpty($response->json('data.0.action'));
    }

    public function test_admin_can_edit_and_delete_any_pt_without_changing_owner(): void
    {
        $milikA = $this->createWithFile($this->ptA);
        $milikB = $this->createWithFile($this->ptB);
        $this->loginAs('admin');

        $this->get('rencanakerja/edit/'.$milikA->id_rencana_kerja)->assertOk();
        $this->put('rencanakerja/update', $this->validPayload([
            'id_rencana_kerja' => $milikA->id_rencana_kerja,
            'judul' => 'Dikoreksi Admin',
            'file' => null,
        ]))->assertJson(['success' => true]);

        $milikA->refresh();
        $this->assertSame('Dikoreksi Admin', $milikA->judul);
        $this->assertSame($this->ptA->npsn, $milikA->kodept);

        $this->put('rencanakerja/destroy', ['id_rencana_kerja' => $milikB->id_rencana_kerja])
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('rencana_kerja', ['id_rencana_kerja' => $milikB->id_rencana_kerja]);
        Storage::disk(RencanaKerja::DISK)->assertMissing($milikB->file_path);
    }

    public function test_admin_can_search_and_order_by_pt_and_file_columns(): void
    {
        $this->createWithFile($this->ptA, ['nama_file' => 'alpha-unik.pdf']);
        $this->createWithFile($this->ptB, ['nama_file' => 'beta.pdf']);
        $this->loginAs('admin');

        $columns = [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
            ['data' => 'pt', 'name' => 'pt.nm_lemb', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'judul', 'name' => 'judul', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'tahun', 'name' => 'tahun', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'file', 'name' => 'nama_file', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'created_at', 'name' => 'created_at', 'searchable' => 'false', 'orderable' => 'true'],
            ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
        ];
        $query = fn (array $extra) => 'rencanakerja/listdataserver?'.http_build_query($extra + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        $this->getJson($query(['search' => ['value' => 'alpha-unik', 'regex' => 'false']]), self::AJAX)
            ->assertOk()->assertJsonPath('recordsFiltered', 1);

        $this->getJson($query(['search' => ['value' => $this->ptB->nm_lemb, 'regex' => 'false']]), self::AJAX)
            ->assertOk()->assertJsonPath('recordsFiltered', 1);

        foreach ([1, 4] as $columnIndex) {
            $this->getJson($query(['order' => [['column' => $columnIndex, 'dir' => 'asc']]]), self::AJAX)
                ->assertOk()->assertJsonPath('recordsTotal', 2);
        }
    }

    public function test_ketua_pjdesa_can_list_and_view_but_not_write(): void
    {
        $rencanaKerja = $this->createWithFile($this->ptA);
        $this->createWithFile($this->ptB);
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        $this->get('rencanakerja')->assertOk()->assertDontSee(route('rencanakerja.tambah'), false);
        $this->listJson()->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('data.0.action', '');
        $this->get('rencanakerja/view/'.$rencanaKerja->id_rencana_kerja)->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->get('rencanakerja/edit/'.$rencanaKerja->id_rencana_kerja)->assertForbidden();
        $this->put('rencanakerja/insert', $this->validPayload())->assertForbidden();
        $this->put('rencanakerja/update', $this->validPayload(['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja]))->assertForbidden();
        $this->put('rencanakerja/destroy', ['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja])->assertForbidden();
        $this->assertDatabaseCount('rencana_kerja', 2);
    }

    public function test_menu_for_all_roles_and_aksi_column_only_for_admin_and_pt(): void
    {
        $menuLink = 'href="'.url('rencanakerja').'"';
        // role => [login, punya kolom Aksi, punya kolom PT]
        $cases = [
            'admin' => [fn () => $this->loginAs('admin'), true, true],
            'pt' => [fn () => $this->loginPt($this->ptA), true, false],
            'kepala' => [fn () => $this->loginAs('kepala'), false, true],
            'pemda' => [fn () => $this->loginAs('pemda'), false, true],
            'dpl' => [fn () => $this->loginAs('dpl'), false, true],
            'mahasiswa' => [fn () => $this->loginAs('mahasiswa'), false, true],
            'ketua' => [function () {
                $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
                Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);
            }, false, true],
        ];

        foreach ($cases as $role => [$login, $hasAksi, $hasPt]) {
            $login();

            $this->get('rencanakerja')->assertOk()->assertSee($menuLink, false);
            $html = $this->get('rencanakerja/listdata')->assertOk()->getContent();

            $this->assertSame($hasAksi, str_contains($html, '>Aksi</th>'), "{$role}: header Aksi");
            $this->assertSame($hasAksi, str_contains($html, "data: 'action'"), "{$role}: kolom JS action");

            [$headers, $jsColumns] = $this->parseListdataColumns($html);
            $this->assertCount(count($jsColumns), $headers, "{$role}: jumlah <th> harus sama dengan kolom JS");
            $this->assertCount(5 + (int) $hasAksi + (int) $hasPt, $headers, "{$role}: jumlah kolom");

            // Sort default harus tetap mengarah ke kolom Diunggah
            preg_match('/order:\s*\[\[columns\.length - (\d+)/', $html, $order);
            $this->assertNotEmpty($order, "{$role}: order default");
            $this->assertSame('created_at', $jsColumns[count($jsColumns) - (int) $order[1]], "{$role}: sort default");
        }
    }

    // Ambil teks <th> di thead dan daftar `data` di array kolom JS
    private function parseListdataColumns(string $html): array
    {
        preg_match('/<thead[^>]*>(.*?)<\/thead>/s', $html, $thead);
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $thead[1] ?? '', $headers);
        preg_match('/var columns = \[(.*?)\];/s', $html, $columns);
        preg_match_all("/\{data:\s*'([^']+)'/", $columns[1] ?? '', $jsColumns);

        return [array_map('trim', $headers[1]), $jsColumns[1]];
    }

    public function test_global_search_and_order_never_touch_sensitive_columns(): void
    {
        $uploader = User::factory()->role('pt')->create(['email' => $this->ptA->npsn]);
        $this->createWithFile($this->ptA)->forceFill(['uploaded_by' => $uploader->id])->save();
        $this->createWithFile($this->ptB);
        $this->loginAs('dpl');

        $columns = [];
        foreach (['uploader.password', 'file_path', 'kodept', 'uploader.email', 'judul'] as $name) {
            $columns[] = ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true',
                'search' => ['value' => '', 'regex' => 'false']];
        }

        foreach ([0, 1, 2, 3] as $orderIndex) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->getJson('rencanakerja/listdataserver?'.http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
                'order' => [['column' => $orderIndex, 'dir' => 'asc']],
                'search' => ['value' => 'rencana-kerja', 'regex' => 'false'],
            ]), self::AJAX)->assertOk()->assertJsonMissingPath('data.0.file_path');

            $sql = strtolower(collect(DB::getQueryLog())->pluck('query')->implode("\n"));
            DB::disableQueryLog();

            $this->assertMatchesRegularExpression('/judul`?\)?\s+like/', $sql, 'kontrol: search kolom whitelist tetap jalan');
            $this->assertStringNotContainsString('password', $sql, "order index {$orderIndex}");
            foreach (['file_path', 'kodept', 'email'] as $column) {
                $this->assertDoesNotMatchRegularExpression('/`?'.$column.'`?\s+like/', $sql, "search {$column}");
                $this->assertDoesNotMatchRegularExpression('/order by[^\n]*'.$column.'/', $sql, "order {$column}");
            }
        }
    }

    public function test_admin_cannot_create(): void
    {
        $this->loginAs('admin');

        $this->get('rencanakerja/tambah')->assertForbidden();
        $this->put('rencanakerja/insert', $this->validPayload())->assertForbidden();

        $this->assertDatabaseCount('rencana_kerja', 0);
    }

    // --- Kepala, Pemda, DPL, Mahasiswa: read-only ---

    public function test_viewer_roles_can_list_and_view_all(): void
    {
        $milikA = $this->createWithFile($this->ptA, ['nama_file' => 'a.pdf']);
        $this->createWithFile($this->ptB);

        foreach (['kepala', 'pemda', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);

            $this->get('rencanakerja')->assertOk()->assertDontSee(route('rencanakerja.tambah'), false);
            $response = $this->listJson()->assertOk()->assertJsonPath('recordsTotal', 2);
            $this->assertSame('', $response->json('data.0.action'), $role);
            $this->assertSame('', $response->json('data.1.action'), $role);

            $this->get('rencanakerja/view/'.$milikA->id_rencana_kerja)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_viewer_roles_cannot_write(): void
    {
        $rencanaKerja = $this->createWithFile($this->ptA);

        foreach (['kepala', 'pemda', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);

            $this->get('rencanakerja/tambah')->assertForbidden();
            $this->get('rencanakerja/edit/'.$rencanaKerja->id_rencana_kerja)->assertForbidden();
            $this->put('rencanakerja/insert', $this->validPayload())->assertForbidden();
            $this->put('rencanakerja/update', $this->validPayload(['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja]))->assertForbidden();
            $this->put('rencanakerja/destroy', ['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja])->assertForbidden();
        }

        $this->assertDatabaseCount('rencana_kerja', 1);
        Storage::disk(RencanaKerja::DISK)->assertExists($rencanaKerja->file_path);
    }

    // --- Role lain & guest ---

    public function test_mahasiswa_without_lokasi_is_redirected_to_profile(): void
    {
        $this->actingAs(User::factory()->role('mahasiswa')->create());

        $this->get('rencanakerja')->assertRedirect(route('mhsprofile'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('rencanakerja')->assertRedirect(route('login'));
        $this->put('rencanakerja/insert', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_listdataserver_requires_ajax(): void
    {
        $this->loginPt($this->ptA);

        $this->get('rencanakerja/listdataserver')->assertNotFound();
    }

    // --- Validasi ---

    public function test_dangerous_invalid_or_oversized_files_are_rejected(): void
    {
        $this->loginPt($this->ptA);
        $rejectedFiles = [
            'php' => UploadedFile::fake()->create('shell.php', 5, 'application/x-php'),
            'php renamed pdf' => $this->realUpload('shell.pdf', '<?php system($_GET["c"]); ?>'),
            'exe' => UploadedFile::fake()->create('virus.exe', 5, 'application/x-msdownload'),
            'html' => UploadedFile::fake()->create('xss.html', 5, 'text/html'),
            'docx' => UploadedFile::fake()->create('rencana.docx', 5, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'xlsx' => UploadedFile::fake()->create('rencana.xlsx', 5, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'too large' => UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf'),
            'missing' => null,
        ];

        foreach ($rejectedFiles as $case => $file) {
            $response = $this->put('rencanakerja/insert', $this->validPayload(['file' => $file]));
            $this->assertFalse($response->json('success'), "Kasus: {$case}");
            $this->assertArrayHasKey('file', $response->json('errors') ?? [], "Kasus: {$case}");
        }

        $this->assertDatabaseCount('rencana_kerja', 0);
        $this->assertSame([], Storage::disk(RencanaKerja::DISK)->allFiles('rencana-kerja'));
    }

    public function test_metadata_validation(): void
    {
        $this->loginPt($this->ptA);

        $this->put('rencanakerja/insert', $this->validPayload([
            'judul' => str_repeat('a', 201),
            'tahun' => '2019',
            'keterangan' => str_repeat('b', 2001),
        ]))->assertJson(['success' => false])
            ->assertJsonValidationErrors(['judul', 'tahun', 'keterangan'], 'errors');

        $this->put('rencanakerja/insert', $this->validPayload([
            'judul' => '',
            'tahun' => (string) (now()->year + 2),
        ]))->assertJsonValidationErrors(['judul', 'tahun'], 'errors');

        $this->assertDatabaseCount('rencana_kerja', 0);
    }

    public function test_upload_exceeding_php_ini_limit_shows_clear_message(): void
    {
        $this->loginPt($this->ptA);
        $failedUpload = new UploadedFile(__FILE__, 'besar.pdf', 'application/pdf', UPLOAD_ERR_INI_SIZE, true);

        $this->put('rencanakerja/insert', $this->validPayload(['file' => $failedUpload]))
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.file.0', fn (string $message) => str_contains($message, 'batas upload server'));
    }

    public function test_client_filename_and_judul_are_sanitized_and_escaped(): void
    {
        $this->loginPt($this->ptA);

        $this->put('rencanakerja/insert', $this->validPayload([
            'judul' => '<script>alert(1)</script>',
            'file' => UploadedFile::fake()->create("ren\r\ncana<b>.pdf", 10, 'application/pdf'),
        ]))->assertJson(['success' => true]);

        $rencanaKerja = RencanaKerja::firstOrFail();
        $this->assertStringNotContainsString("\r", $rencanaKerja->nama_file);
        $this->assertStringNotContainsString("\n", $rencanaKerja->nama_file);

        $content = $this->listJson()->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $content);
        $this->assertStringNotContainsString('cana<b>', $content);
    }

    public function test_view_missing_file_returns_404(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = RencanaKerja::factory()->forPt($this->ptA->npsn)->create();

        $this->get('rencanakerja/view/'.$rencanaKerja->id_rencana_kerja)->assertNotFound();
    }

    // Data lama sebelum upload dibatasi PDF tidak disajikan inline
    public function test_view_non_pdf_returns_404(): void
    {
        $this->loginPt($this->ptA);
        $rencanaKerja = $this->createWithFile($this->ptA, ['mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        $this->get('rencanakerja/view/'.$rencanaKerja->id_rencana_kerja)->assertNotFound();
    }

    public function test_listdataserver_ignores_non_whitelisted_columns(): void
    {
        $uploader = User::factory()->role('pt')->create(['email' => $this->ptA->npsn]);
        $rencanaKerja = $this->createWithFile($this->ptA);
        $rencanaKerja->forceFill(['uploaded_by' => $uploader->id])->save();
        $this->createWithFile($this->ptB);
        $this->loginAs('mahasiswa');

        $probes = [
            'uploader.password' => substr($uploader->password, 0, 7),
            'file_path' => 'rencana-kerja',
            'kodept' => $this->ptA->npsn,
        ];

        foreach ($probes as $column => $value) {
            $query = http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10,
                'columns' => [['data' => $column, 'name' => $column, 'searchable' => 'true', 'orderable' => 'true',
                    'search' => ['value' => $value, 'regex' => 'false']]],
                'order' => [['column' => 0, 'dir' => 'asc']],
                'search' => ['value' => '', 'regex' => 'false'],
            ]);

            $this->getJson('rencanakerja/listdataserver?'.$query, self::AJAX)
                ->assertOk()->assertJsonPath('recordsFiltered', 2);
        }
    }

    // --- Model / DB ---

    public function test_policy_is_auto_discovered(): void
    {
        $this->assertInstanceOf(\App\Policies\RencanaKerjaPolicy::class, \Illuminate\Support\Facades\Gate::getPolicyFor(RencanaKerja::class));
    }

    public function test_deleting_pt_cascades_rencana_kerja(): void
    {
        $rencanaKerja = $this->createWithFile($this->ptA);

        $this->ptA->delete();

        $this->assertDatabaseMissing('rencana_kerja', ['id_rencana_kerja' => $rencanaKerja->id_rencana_kerja]);
    }
}
