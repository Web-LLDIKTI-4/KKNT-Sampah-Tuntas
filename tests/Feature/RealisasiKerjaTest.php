<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\RealisasiKerja;
use App\Models\Satuanpendidikan;
use App\Models\User;

class RealisasiKerjaTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RealisasiKerja::DISK);
        $this->ptA = Satuanpendidikan::factory()->create();
        $this->ptB = Satuanpendidikan::factory()->create();
    }

    private function loginPt(Satuanpendidikan $pt): User
    {
        return $this->loginAs('pt', ['email' => $pt->npsn]);
    }

    // Relasi ke PT dibuat sebelum request pertama agar relasi user tidak basi
    private function loginScoped(string $role, ?Satuanpendidikan $pt, array $attributes = []): User
    {
        $user = $this->loginAs($role, $attributes);
        $kodept = $pt?->npsn;

        if ($role === 'dpl') {
            Dpl::factory()->create(['email' => $user->email, 'kodept' => $kodept]);
        } else {
            Mahasiswa::where('email', $user->email)->update(['kodept' => $kodept]);
        }

        return $user;
    }

    private function loginKetua(?Satuanpendidikan $pt): User
    {
        $user = $this->loginScoped('mahasiswa', $pt, ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'judul' => 'Realisasi Kerja Semester Ganjil',
            'keterangan' => 'Program pengelolaan sampah desa.',
            'file' => UploadedFile::fake()->create('realisasi.pdf', 120, 'application/pdf'),
        ];
    }

    private function createWithFile(Satuanpendidikan $pt, array $attributes = []): RealisasiKerja
    {
        $filePath = UploadedFile::fake()->create('lama.pdf', 50, 'application/pdf')->store('realisasi-kerja', RealisasiKerja::DISK);

        return RealisasiKerja::factory()->forPt($pt->npsn)->create($attributes + ['file_path' => $filePath, 'nama_file' => 'lama.pdf']);
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
        return $this->getJson('realisasikerja/listdataserver?draw=1&start=0&length=50', self::AJAX);
    }

    // --- PT: CRUD miliknya ---

    public function test_pt_can_open_pages_and_insert_own_realisasi_kerja(): void
    {
        $user = $this->loginPt($this->ptA);

        $this->get('realisasikerja')->assertOk()->assertSee(route('realisasikerja.tambah'), false);
        $this->get('realisasikerja/listdata')->assertOk()->assertSee(route('realisasikerja.listdataserver'), false);
        $this->get('realisasikerja/tambah')->assertOk()->assertSee('enctype="multipart/form-data"', false);

        $this->put('realisasikerja/insert', $this->validPayload())->assertJson(['success' => true]);

        $realisasiKerja = RealisasiKerja::where('judul', 'Realisasi Kerja Semester Ganjil')->firstOrFail();
        Storage::disk(RealisasiKerja::DISK)->assertExists($realisasiKerja->file_path);
        $this->assertStringStartsWith('realisasi-kerja/', $realisasiKerja->file_path);
        $this->assertSame($this->ptA->npsn, $realisasiKerja->kodept);
        $this->assertSame($user->id, $realisasiKerja->uploaded_by);
        $this->assertSame('realisasi.pdf', $realisasiKerja->nama_file);
    }

    public function test_pt_cannot_set_kodept_or_uploaded_by_via_payload(): void
    {
        $user = $this->loginPt($this->ptA);
        $other = User::factory()->role('admin')->create();

        $this->put('realisasikerja/insert', $this->validPayload([
            'kodept' => $this->ptB->npsn,
            'uploaded_by' => $other->id,
        ]))->assertJson(['success' => true]);

        $this->assertDatabaseHas('realisasi_kerja', ['kodept' => $this->ptA->npsn, 'uploaded_by' => $user->id]);
        $this->assertDatabaseMissing('realisasi_kerja', ['kodept' => $this->ptB->npsn]);

        $realisasiKerja = RealisasiKerja::firstOrFail();
        $this->put('realisasikerja/update', [
            'id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja,
            'judul' => 'Ubah',
            'kodept' => $this->ptB->npsn,
        ])->assertJson(['success' => true]);

        $this->assertSame($this->ptA->npsn, $realisasiKerja->fresh()->kodept);
    }

    public function test_pt_can_update_own_and_new_file_replaces_old(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = $this->createWithFile($this->ptA);
        $oldFilePath = $realisasiKerja->file_path;

        $this->get('realisasikerja/edit/'.$realisasiKerja->id_realisasi_kerja)->assertOk()
            ->assertSee($realisasiKerja->id_realisasi_kerja);

        $this->put('realisasikerja/update', $this->validPayload([
            'id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja,
            'judul' => 'Realisasi Baru',
            'file' => UploadedFile::fake()->create('baru.pdf', 30, 'application/pdf'),
        ]))->assertJson(['success' => true]);

        $realisasiKerja->refresh();
        Storage::disk(RealisasiKerja::DISK)->assertMissing($oldFilePath);
        Storage::disk(RealisasiKerja::DISK)->assertExists($realisasiKerja->file_path);
        $this->assertSame('baru.pdf', $realisasiKerja->nama_file);
        $this->assertSame('Realisasi Baru', $realisasiKerja->judul);
    }

    public function test_update_without_file_keeps_existing_file(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = $this->createWithFile($this->ptA);

        $this->put('realisasikerja/update', $this->validPayload([
            'id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja,
            'judul' => 'Judul Saja',
            'file' => null,
        ]))->assertJson(['success' => true]);

        Storage::disk(RealisasiKerja::DISK)->assertExists($realisasiKerja->file_path);
        $this->assertSame($realisasiKerja->file_path, $realisasiKerja->fresh()->file_path);
        $this->assertSame('Judul Saja', $realisasiKerja->fresh()->judul);
    }

    public function test_pt_can_delete_own_and_file_is_removed(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = $this->createWithFile($this->ptA);

        $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja])
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('realisasi_kerja', ['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja]);
        Storage::disk(RealisasiKerja::DISK)->assertMissing($realisasiKerja->file_path);
    }

    public function test_pt_can_view_own_pdf_inline(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = $this->createWithFile($this->ptA, ['nama_file' => 'realisasi-2026.pdf']);

        $response = $this->get('realisasikerja/view/'.$realisasiKerja->id_realisasi_kerja)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('realisasi-2026.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_download_route_no_longer_exists(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = $this->createWithFile($this->ptA);

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('realisasikerja.download'));
        $this->get('realisasikerja/download/'.$realisasiKerja->id_realisasi_kerja)->assertNotFound();
        $this->listJson()->assertOk()->assertJsonPath('data.0.file', fn (string $cell) => str_contains($cell, route('realisasikerja.view', $realisasiKerja->id_realisasi_kerja))
            && str_contains($cell, 'target="_blank"') && ! str_contains($cell, 'download'));
    }

    // --- IDOR antar PT ---

    public function test_pt_list_only_contains_own_rows_without_file_path(): void
    {
        $this->createWithFile($this->ptA, ['judul' => 'Milik A']);
        $this->createWithFile($this->ptB, ['judul' => 'Milik B']);
        $this->loginPt($this->ptA);

        $this->get('realisasikerja/listdata')->assertOk()->assertDontSee("{data: 'pt'", false);

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

        $this->get('realisasikerja/edit/'.$milikB->id_realisasi_kerja)->assertNotFound();
        $this->get('realisasikerja/view/'.$milikB->id_realisasi_kerja)->assertNotFound();

        $this->put('realisasikerja/update', $this->validPayload([
            'id_realisasi_kerja' => $milikB->id_realisasi_kerja,
            'judul' => 'Dibajak',
        ]))->assertNotFound()->assertJson(['success' => false]);

        $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => $milikB->id_realisasi_kerja])
            ->assertNotFound()->assertJson(['success' => false]);

        $this->assertSame('Milik B', $milikB->fresh()->judul);
        Storage::disk(RealisasiKerja::DISK)->assertExists($milikB->file_path);
    }

    public function test_invalid_ids_are_rejected(): void
    {
        $this->loginPt($this->ptA);

        $this->get('realisasikerja/edit/bukan-uuid')->assertNotFound();
        $this->get('realisasikerja/view/1')->assertNotFound();
        $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => 'bukan-uuid'])->assertNotFound();
        $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => ['array']])->assertNotFound();
        $this->put('realisasikerja/update', $this->validPayload(['id_realisasi_kerja' => 'x']))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('id_realisasi_kerja', 'errors');
    }

    public function test_pt_without_satuanpendidikan_cannot_create(): void
    {
        $this->loginAs('pt', ['email' => '99999999']);

        $this->get('realisasikerja')->assertOk()->assertDontSee(route('realisasikerja.tambah'), false);
        $this->get('realisasikerja/tambah')->assertForbidden();
        $this->put('realisasikerja/insert', $this->validPayload())->assertForbidden();

        $this->assertDatabaseCount('realisasi_kerja', 0);
    }

    // --- Admin ---

    public function test_admin_sees_all_with_pt_column_and_action_buttons(): void
    {
        $this->createWithFile($this->ptA);
        $this->createWithFile($this->ptB);
        $this->loginAs('admin');

        $this->get('realisasikerja')->assertOk()->assertDontSee(route('realisasikerja.tambah'), false);

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

        $this->get('realisasikerja/edit/'.$milikA->id_realisasi_kerja)->assertOk();
        $this->put('realisasikerja/update', $this->validPayload([
            'id_realisasi_kerja' => $milikA->id_realisasi_kerja,
            'judul' => 'Dikoreksi Admin',
            'file' => null,
        ]))->assertJson(['success' => true]);

        $milikA->refresh();
        $this->assertSame('Dikoreksi Admin', $milikA->judul);
        $this->assertSame($this->ptA->npsn, $milikA->kodept);

        $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => $milikB->id_realisasi_kerja])
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('realisasi_kerja', ['id_realisasi_kerja' => $milikB->id_realisasi_kerja]);
        Storage::disk(RealisasiKerja::DISK)->assertMissing($milikB->file_path);
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
            ['data' => 'file', 'name' => 'nama_file', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'created_at', 'name' => 'created_at', 'searchable' => 'false', 'orderable' => 'true'],
            ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
        ];
        $query = fn (array $extra) => 'realisasikerja/listdataserver?'.http_build_query($extra + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        $this->getJson($query(['search' => ['value' => 'alpha-unik', 'regex' => 'false']]), self::AJAX)
            ->assertOk()->assertJsonPath('recordsFiltered', 1);

        $this->getJson($query(['search' => ['value' => $this->ptB->nm_lemb, 'regex' => 'false']]), self::AJAX)
            ->assertOk()->assertJsonPath('recordsFiltered', 1);

        foreach ([1, 3] as $columnIndex) {
            $this->getJson($query(['order' => [['column' => $columnIndex, 'dir' => 'asc']]]), self::AJAX)
                ->assertOk()->assertJsonPath('recordsTotal', 2);
        }
    }

    public function test_ketua_pjdesa_can_list_and_view_but_not_write(): void
    {
        $realisasiKerja = $this->createWithFile($this->ptA);
        $milikB = $this->createWithFile($this->ptB);
        $this->loginKetua($this->ptA);

        $this->get('realisasikerja')->assertOk()->assertDontSee(route('realisasikerja.tambah'), false);
        $this->listJson()->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.action', '');
        $this->get('realisasikerja/view/'.$realisasiKerja->id_realisasi_kerja)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('realisasikerja/view/'.$milikB->id_realisasi_kerja)->assertNotFound();

        $this->get('realisasikerja/edit/'.$realisasiKerja->id_realisasi_kerja)->assertForbidden();
        $this->put('realisasikerja/insert', $this->validPayload())->assertForbidden();
        $this->put('realisasikerja/update', $this->validPayload(['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja]))->assertForbidden();
        $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja])->assertForbidden();
        $this->assertDatabaseCount('realisasi_kerja', 2);
    }

    public function test_menu_for_all_roles_and_aksi_column_only_for_admin_and_pt(): void
    {
        $menuLink = 'href="'.url('realisasikerja').'"';
        // role => [login, punya kolom Aksi, punya kolom PT]
        $cases = [
            'admin' => [fn () => $this->loginAs('admin'), true, true],
            'pt' => [fn () => $this->loginPt($this->ptA), true, false],
            'kepala' => [fn () => $this->loginAs('kepala'), false, true],
            'pemda' => [fn () => $this->loginAs('pemda'), false, true],
            'dpl' => [fn () => $this->loginScoped('dpl', $this->ptA), false, false],
            'mahasiswa' => [fn () => $this->loginScoped('mahasiswa', $this->ptA), false, false],
            'ketua' => [fn () => $this->loginKetua($this->ptA), false, false],
        ];

        foreach ($cases as $role => [$login, $hasAksi, $hasPt]) {
            $login();

            $this->get('realisasikerja')->assertOk()->assertSee($menuLink, false);
            $html = $this->get('realisasikerja/listdata')->assertOk()->getContent();

            $this->assertSame($hasAksi, str_contains($html, '>Aksi</th>'), "{$role}: header Aksi");
            $this->assertSame($hasAksi, str_contains($html, "data: 'action'"), "{$role}: kolom JS action");

            [$headers, $jsColumns] = $this->parseListdataColumns($html);
            $this->assertCount(count($jsColumns), $headers, "{$role}: jumlah <th> harus sama dengan kolom JS");
            $this->assertCount(4 + (int) $hasAksi + (int) $hasPt, $headers, "{$role}: jumlah kolom");
            $this->assertSame($hasPt, in_array('Perguruan Tinggi', $headers, true), "{$role}: header PT");
            $this->assertSame($hasPt, in_array('pt', $jsColumns, true), "{$role}: kolom JS pt");
            $this->assertNotContains('tahun', $jsColumns, "{$role}: kolom tahun dihapus");

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
        $this->loginScoped('dpl', $this->ptA);

        $columns = [];
        foreach (['uploader.password', 'file_path', 'kodept', 'uploader.email', 'judul'] as $name) {
            $columns[] = ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true',
                'search' => ['value' => '', 'regex' => 'false']];
        }

        foreach ([0, 1, 2, 3] as $orderIndex) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->getJson('realisasikerja/listdataserver?'.http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
                'order' => [['column' => $orderIndex, 'dir' => 'asc']],
                'search' => ['value' => 'realisasi-kerja', 'regex' => 'false'],
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

        $this->get('realisasikerja/tambah')->assertForbidden();
        $this->put('realisasikerja/insert', $this->validPayload())->assertForbidden();

        $this->assertDatabaseCount('realisasi_kerja', 0);
    }

    // --- Kepala, Pemda, DPL, Mahasiswa: read-only ---

    public function test_kepala_and_pemda_can_list_and_view_all_pt(): void
    {
        $milikA = $this->createWithFile($this->ptA, ['nama_file' => 'a.pdf']);
        $milikB = $this->createWithFile($this->ptB);

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);

            $this->get('realisasikerja')->assertOk()->assertDontSee(route('realisasikerja.tambah'), false);
            $response = $this->listJson()->assertOk()->assertJsonPath('recordsTotal', 2);
            $this->assertSame('', $response->json('data.0.action'), $role);
            $this->assertSame('', $response->json('data.1.action'), $role);
            $names = collect($response->json('data'))->pluck('pt')->all();
            $this->assertEqualsCanonicalizing([e($this->ptA->nm_lemb), e($this->ptB->nm_lemb)], $names, $role);

            $this->get('realisasikerja/view/'.$milikA->id_realisasi_kerja)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get('realisasikerja/view/'.$milikB->id_realisasi_kerja)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_dpl_mahasiswa_and_ketua_only_see_own_pt(): void
    {
        $milikA = $this->createWithFile($this->ptA, ['judul' => 'Milik A']);
        $milikB = $this->createWithFile($this->ptB, ['judul' => 'Milik B']);
        $logins = [
            'dpl' => fn () => $this->loginScoped('dpl', $this->ptA),
            'mahasiswa' => fn () => $this->loginScoped('mahasiswa', $this->ptA),
            'ketua' => fn () => $this->loginKetua($this->ptA),
        ];

        foreach ($logins as $role => $login) {
            $login();

            $response = $this->listJson()->assertOk()
                ->assertJsonPath('recordsTotal', 1);
            $this->assertStringContainsString('Milik A', $response->json('data.0.judul'), $role);
            $this->assertStringNotContainsString('Milik B', $response->getContent(), $role);
            $this->assertSame('', $response->json('data.0.action'), $role);

            $this->get('realisasikerja/view/'.$milikA->id_realisasi_kerja)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get('realisasikerja/view/'.$milikB->id_realisasi_kerja)->assertNotFound();
            $this->get('realisasikerja/edit/'.$milikB->id_realisasi_kerja)->assertNotFound();
        }
    }

    public function test_dpl_and_mahasiswa_without_kodept_see_nothing(): void
    {
        $milikA = $this->createWithFile($this->ptA);
        $this->createWithFile($this->ptB);
        $logins = [
            'dpl tanpa record Dpl' => fn () => $this->loginAs('dpl'),
            'dpl kodept null' => fn () => $this->loginScoped('dpl', null),
            'mahasiswa kodept null' => fn () => $this->loginScoped('mahasiswa', null),
            'ketua kodept null' => fn () => $this->loginKetua(null),
        ];

        foreach ($logins as $case => $login) {
            $login();

            $this->get('realisasikerja')->assertOk();
            $this->listJson()->assertOk()->assertJsonPath('recordsTotal', 0)->assertJsonCount(0, 'data');
            $this->get('realisasikerja/view/'.$milikA->id_realisasi_kerja)->assertNotFound();
        }
    }

    public function test_viewer_roles_cannot_write(): void
    {
        $realisasiKerja = $this->createWithFile($this->ptA);
        $logins = [
            'kepala' => fn () => $this->loginAs('kepala'),
            'pemda' => fn () => $this->loginAs('pemda'),
            'dpl' => fn () => $this->loginScoped('dpl', $this->ptA),
            'mahasiswa' => fn () => $this->loginScoped('mahasiswa', $this->ptA),
        ];

        foreach ($logins as $role => $login) {
            $login();

            $this->get('realisasikerja/tambah')->assertForbidden();
            $this->get('realisasikerja/edit/'.$realisasiKerja->id_realisasi_kerja)->assertForbidden();
            $this->put('realisasikerja/insert', $this->validPayload())->assertForbidden();
            $this->put('realisasikerja/update', $this->validPayload(['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja]))->assertForbidden();
            $this->put('realisasikerja/destroy', ['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja])->assertForbidden();
        }

        $this->assertDatabaseCount('realisasi_kerja', 1);
        Storage::disk(RealisasiKerja::DISK)->assertExists($realisasiKerja->file_path);
    }

    // --- Role lain & guest ---

    public function test_mahasiswa_without_lokasi_is_redirected_to_profile(): void
    {
        $this->actingAs(User::factory()->role('mahasiswa')->create());

        $this->get('realisasikerja')->assertRedirect(route('mhsprofile'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('realisasikerja')->assertRedirect(route('login'));
        $this->put('realisasikerja/insert', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_listdataserver_requires_ajax(): void
    {
        $this->loginPt($this->ptA);

        $this->get('realisasikerja/listdataserver')->assertNotFound();
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
            'docx' => UploadedFile::fake()->create('realisasi.docx', 5, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'xlsx' => UploadedFile::fake()->create('realisasi.xlsx', 5, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'too large' => UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf'),
            'missing' => null,
        ];

        foreach ($rejectedFiles as $case => $file) {
            $response = $this->put('realisasikerja/insert', $this->validPayload(['file' => $file]));
            $this->assertFalse($response->json('success'), "Kasus: {$case}");
            $this->assertArrayHasKey('file', $response->json('errors') ?? [], "Kasus: {$case}");
        }

        $this->assertDatabaseCount('realisasi_kerja', 0);
        $this->assertSame([], Storage::disk(RealisasiKerja::DISK)->allFiles('realisasi-kerja'));
    }

    public function test_metadata_validation(): void
    {
        $this->loginPt($this->ptA);

        $this->put('realisasikerja/insert', $this->validPayload([
            'judul' => str_repeat('a', 201),
            'keterangan' => str_repeat('b', 2001),
        ]))->assertJson(['success' => false])
            ->assertJsonValidationErrors(['judul', 'keterangan'], 'errors')
            ->assertJsonMissingValidationErrors('tahun', 'errors');

        $this->put('realisasikerja/insert', $this->validPayload([
            'judul' => '',
        ]))->assertJsonValidationErrors(['judul'], 'errors');

        $this->assertDatabaseCount('realisasi_kerja', 0);
    }

    public function test_tahun_input_is_ignored_and_column_is_dropped(): void
    {
        $this->assertFalse(Schema::hasColumn('realisasi_kerja', 'tahun'));
        $this->assertNotContains('tahun', (new RealisasiKerja)->getFillable());

        $this->loginPt($this->ptA);

        $this->put('realisasikerja/insert', $this->validPayload(['tahun' => 'bukan-tahun']))
            ->assertJson(['success' => true]);

        $realisasiKerja = RealisasiKerja::firstOrFail();
        $this->assertArrayNotHasKey('tahun', $realisasiKerja->fresh()->getAttributes());

        $this->put('realisasikerja/update', [
            'id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja,
            'judul' => 'Tanpa Tahun',
            'tahun' => '1900',
        ])->assertJson(['success' => true]);

        $this->assertSame('Tanpa Tahun', $realisasiKerja->fresh()->judul);
        $this->assertArrayNotHasKey('tahun', $realisasiKerja->fresh()->getAttributes());
    }

    public function test_upload_exceeding_php_ini_limit_shows_clear_message(): void
    {
        $this->loginPt($this->ptA);
        $failedUpload = new UploadedFile(__FILE__, 'besar.pdf', 'application/pdf', UPLOAD_ERR_INI_SIZE, true);

        $this->put('realisasikerja/insert', $this->validPayload(['file' => $failedUpload]))
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.file.0', fn (string $message) => str_contains($message, 'batas upload server'));
    }

    public function test_client_filename_and_judul_are_sanitized_and_escaped(): void
    {
        $this->loginPt($this->ptA);

        $this->put('realisasikerja/insert', $this->validPayload([
            'judul' => '<script>alert(1)</script>',
            'file' => UploadedFile::fake()->create("real\r\nisasi<b>.pdf", 10, 'application/pdf'),
        ]))->assertJson(['success' => true]);

        $realisasiKerja = RealisasiKerja::firstOrFail();
        $this->assertStringNotContainsString("\r", $realisasiKerja->nama_file);
        $this->assertStringNotContainsString("\n", $realisasiKerja->nama_file);

        $content = $this->listJson()->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $content);
        $this->assertStringNotContainsString('isasi<b>', $content);
    }

    public function test_view_missing_file_returns_404(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = RealisasiKerja::factory()->forPt($this->ptA->npsn)->create();

        $this->get('realisasikerja/view/'.$realisasiKerja->id_realisasi_kerja)->assertNotFound();
    }

    // Data lama sebelum upload dibatasi PDF tidak disajikan inline
    public function test_view_non_pdf_returns_404(): void
    {
        $this->loginPt($this->ptA);
        $realisasiKerja = $this->createWithFile($this->ptA, ['mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        $this->get('realisasikerja/view/'.$realisasiKerja->id_realisasi_kerja)->assertNotFound();
    }

    public function test_listdataserver_ignores_non_whitelisted_columns(): void
    {
        $uploader = User::factory()->role('pt')->create(['email' => $this->ptA->npsn]);
        $realisasiKerja = $this->createWithFile($this->ptA);
        $realisasiKerja->forceFill(['uploaded_by' => $uploader->id])->save();
        $this->createWithFile($this->ptA);
        $this->createWithFile($this->ptB);
        $this->loginScoped('mahasiswa', $this->ptA);

        // Probe password/kodept: jika filter kolom diterapkan, hasil < 2
        $probes = [
            'uploader.password' => substr($uploader->password, 0, 7),
            'file_path' => 'realisasi-kerja',
            'kodept' => $this->ptB->npsn,
        ];

        foreach ($probes as $column => $value) {
            $query = http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10,
                'columns' => [['data' => $column, 'name' => $column, 'searchable' => 'true', 'orderable' => 'true',
                    'search' => ['value' => $value, 'regex' => 'false']]],
                'order' => [['column' => 0, 'dir' => 'asc']],
                'search' => ['value' => '', 'regex' => 'false'],
            ]);

            $this->getJson('realisasikerja/listdataserver?'.$query, self::AJAX)
                ->assertOk()->assertJsonPath('recordsFiltered', 2);
        }
    }

    // --- Model / DB ---

    public function test_policy_is_auto_discovered(): void
    {
        $this->assertInstanceOf(\App\Policies\RealisasiKerjaPolicy::class, \Illuminate\Support\Facades\Gate::getPolicyFor(RealisasiKerja::class));
    }

    public function test_deleting_pt_cascades_realisasi_kerja(): void
    {
        $realisasiKerja = $this->createWithFile($this->ptA);

        $this->ptA->delete();

        $this->assertDatabaseMissing('realisasi_kerja', ['id_realisasi_kerja' => $realisasiKerja->id_realisasi_kerja]);
    }
}
