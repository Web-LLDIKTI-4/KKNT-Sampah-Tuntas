<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\RequirePelaksanaanKkn;
use App\Http\Requests\Admin\KodeptRequest;
use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\PelaksanaanKkn;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerguruanTinggiSaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_upserts_only_known_columns(): void
    {
        $this->loginAs('admin');
        $idSp = (string) Str::uuid();
        Http::fake(['*/splldikti4/*' => Http::response([
            ['id_sp' => $idSp, 'npsn' => '041001', 'nm_lemb' => 'Universitas Uji', 'kolom_asing' => 'x'],
            ['npsn' => '041002'],
        ])]);

        $this->put('perguruantinggi/getdata')
            ->assertJson(['success' => true, 'messages' => '0 Data berhasil di update dan 1 Data berhasil disimpan!']);
        $this->assertDatabaseHas('ref_satuanpendidikan', ['id_sp' => $idSp, 'nm_lemb' => 'Universitas Uji']);
    }

    public function test_sync_reports_connection_error(): void
    {
        $this->loginAs('admin');
        Http::fake(['*' => Http::response('error', 500)]);

        $this->put('perguruantinggi/getdata')->assertJson(['success' => false, 'messages' => 'Koneksi ke PDDIKTI error!']);
    }

    public function test_insert_validates_kodept_format(): void
    {
        $this->loginAs('admin');
        Http::fake();

        $this->put('perguruantinggi/insert', ['kodept' => '12; DROP'])->assertJsonValidationErrors('kodept', 'errors');
        Http::assertNothingSent();
    }

    public function test_public_saran_is_validated_and_throttled(): void
    {
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'bukan-email', 'saran' => 'x'])
            ->assertJsonValidationErrors('email', 'errors');
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'a@pps.test', 'saran' => 'Bagus'])->assertJson(['success' => true]);
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'a@pps.test', 'saran' => 'Lagi'])
            ->assertJsonPath('errors.email.0', 'Pesan anda sudah ada!');

        foreach (range(1, 3) as $i) {
            $this->put('saran/insert', ['nama' => 'A', 'email' => "x$i@pps.test", 'saran' => 'x']);
        }
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'z@pps.test', 'saran' => 'x'])->assertStatus(429);
    }

    public function test_public_peserta_counts_per_pt(): void
    {
        $sp = Satuanpendidikan::factory()->create();
        Mahasiswa::factory()->count(2)->create(['kodept' => $sp->npsn]);

        $this->getJson('ptpeserta/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.jumlah_mhs', 2)->assertJsonPath('data.0.nm_lemb', $sp->nm_lemb);
    }

    private const NON_ADMIN = ['dpl', 'pt', 'kepala', 'pemda'];

    // Lock pelaksanaan aktif + PT sudah mengisi pelaksanaan: user lolos RequirePelaksanaanKkn
    private function loginUnlocked(string $role): User
    {
        config(['app.pelaksanaan_lock' => true]);
        $pt = Satuanpendidikan::factory()->create();
        PelaksanaanKkn::factory()->forPt($pt->npsn)->create();
        $user = $this->loginAs($role);
        if ($role === 'dpl') {
            Dpl::factory()->create(['email' => $user->email, 'kodept' => $pt->npsn]);
        }
        $user = $user->fresh();
        $this->assertFalse($user->isLockedByPelaksanaan(), "$role tidak boleh terkunci pelaksanaan");
        $this->actingAs($user);

        return $user;
    }

    public function test_write_routes_are_in_admin_only_group(): void
    {
        foreach ([['PUT', 'perguruantinggi/getdata'], ['GET', 'perguruantinggi/tambah'], ['PUT', 'perguruantinggi/insert']] as [$method, $uri]) {
            $middleware = Route::getRoutes()->match(request()->create($uri, $method))->gatherMiddleware();
            $this->assertContains('role:admin', $middleware, $uri);
            $this->assertEmpty(array_filter($middleware, fn ($m) => is_string($m) && str_starts_with($m, 'role:') && $m !== 'role:admin'), $uri);
        }
    }

    public function test_non_admin_is_rejected_from_write_routes_by_admin_rule(): void
    {
        foreach (self::NON_ADMIN as $role) {
            Http::fake(['*' => Http::response([['id_sp' => (string) Str::uuid(), 'npsn' => '041999', 'nm_lemb' => 'PT Liar']])]);
            $this->loginUnlocked($role);

            $pemantau = in_array($role, ['kepala', 'pemda'], true);
            foreach ([
                [$this->put('perguruantinggi/getdata'), $pemantau],
                [$this->get('perguruantinggi/tambah'), false],
                [$this->put('perguruantinggi/insert', ['kodept' => '041999']), $pemantau],
            ] as [$response, $readOnly]) {
                // Kepala/pemda: PUT ditolak lebih awal oleh KepalaReadOnly (403); selain itu role:admin (redirect home)
                $readOnly
                    ? $response->assertForbidden()->assertSee('Akun pemantau hanya dapat melihat data.')
                    : $response->assertRedirect(route('home'));
                $this->assertNotSame(RequirePelaksanaanKkn::MESSAGE, session('error'), $role);
            }

            Http::assertNothingSent();
            $this->assertDatabaseMissing('ref_satuanpendidikan', ['npsn' => '041999']);
        }
    }

    public function test_kodept_request_authorizes_admin_only(): void
    {
        // Route uji tanpa role middleware: membuktikan KodeptRequest::authorize() menolak sendiri (defense in depth)
        Route::middleware(['web', 'auth'])->put('_qa/kodept', fn (KodeptRequest $request) => response()->json(['ok' => true]));

        // Kepala/pemda sudah ditolak KepalaReadOnly untuk semua PUT; authorize() diuji dengan role yang lolos middleware itu
        foreach (['dpl', 'pt'] as $role) {
            $this->loginUnlocked($role);
            $this->putJson('_qa/kodept', ['kodept' => '041998'])
                ->assertForbidden()->assertExactJson(['success' => false, 'message' => 'Anda tidak memiliki akses untuk data ini.']);
        }

        $this->loginUnlocked('admin');
        $this->putJson('_qa/kodept', ['kodept' => '041998'])->assertOk()->assertExactJson(['ok' => true]);
    }

    public function test_admin_can_still_sync_open_tambah_and_insert(): void
    {
        $this->loginUnlocked('admin');
        $idSp = (string) Str::uuid();
        Http::fake([
            '*/splldikti4/*' => Http::response([['id_sp' => (string) Str::uuid(), 'npsn' => '041201', 'nm_lemb' => 'Univ Sync']]),
            '*/satuanpendidikanall/*' => Http::response([['id_sp' => $idSp, 'npsn' => '041202', 'nm_lemb' => 'Univ Tambah']]),
        ]);

        $this->put('perguruantinggi/getdata')->assertOk()->assertJson(['success' => true]);
        $this->get('perguruantinggi/tambah')->assertOk();
        $this->put('perguruantinggi/insert', ['kodept' => '041202'])
            ->assertOk()->assertJson(['success' => true, 'messages' => 'Data perguruan tinggi berhasil dimasukkan']);

        $this->assertDatabaseHas('ref_satuanpendidikan', ['npsn' => '041201', 'nm_lemb' => 'Univ Sync']);
        $this->assertDatabaseHas('ref_satuanpendidikan', ['id_sp' => $idSp, 'nm_lemb' => 'Univ Tambah']);
    }

    public function test_read_routes_stay_open_for_previous_roles(): void
    {
        Satuanpendidikan::factory()->create(['nm_lemb' => 'Univ Baca']);

        foreach (['admin', ...self::NON_ADMIN] as $role) {
            $this->loginUnlocked($role);
            $this->get('perguruantinggi')->assertOk();
            $this->get('perguruantinggi/listdata')->assertOk();
            $this->getJson('perguruantinggi/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->assertJsonFragment(['nm_lemb' => 'Univ Baca']);
        }
    }

    public function test_sync_and_tambah_buttons_render_only_for_admin(): void
    {
        $this->loginUnlocked('admin');
        $this->get('perguruantinggi')->assertOk()
            ->assertSee('Sync PDDIKTI')->assertSee('Tambah Perguruan Tinggi')
            ->assertSee(url('perguruantinggi/getdata'), false)->assertSee(url('perguruantinggi/tambah'), false);

        foreach (self::NON_ADMIN as $role) {
            $this->loginUnlocked($role);
            $this->get('perguruantinggi')->assertOk()
                ->assertDontSee('Sync PDDIKTI')->assertDontSee('Tambah Perguruan Tinggi')
                ->assertDontSee(url('perguruantinggi/getdata'), false)->assertDontSee(url('perguruantinggi/tambah'), false)
                ->assertDontSee('#form-tambah', false)->assertDontSee('#tambahpilih', false)
                ->assertSee(url('perguruantinggi/listdata'), false);
        }
    }
}
