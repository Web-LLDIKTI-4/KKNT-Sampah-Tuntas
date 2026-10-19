<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

// Kelola Pengguna: 1 user : 1 record mahasiswa/DPL, listdataserver caps & escaping, export keyword, authorization
class UserAccountIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Satuanpendidikan $sp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sp = Satuanpendidikan::factory()->create();
    }

    // ---- Bulk insert (user/insert) & insertuser (user/insertuser) ----

    public function test_bulk_rejects_empty_selection(): void
    {
        $this->loginAs('admin');

        foreach (['user/insert', 'user/insertuser'] as $uri) {
            foreach ([[], ['createuser' => []], ['createuser' => ['', '   ']], ['createuser' => [['nested']]]] as $payload) {
                $this->put($uri, $payload)->assertOk()->assertJson(['error' => 'user harus dipilih']);
            }
        }
        $tooMany = array_map(fn ($i) => "x{$i}@pps.test", range(1, 1001));
        $this->put('user/insert', ['createuser' => $tooMany])->assertOk()->assertJson(['error' => 'Maksimal 1000 user per proses.']);
        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_bulk_rejects_whole_batch_when_one_email_does_not_match(): void
    {
        $this->loginAs('admin');
        $mhs = $this->mahasiswa();
        $dpl = $this->dpl();

        $this->put('user/insert', ['createuser' => [$mhs->email, 'tidak-ada@pps.test']])
            ->assertOk()->assertJsonPath('error', fn ($error) => str_contains($error, 'tidak-ada@pps.test') && ! str_contains($error, $mhs->email));
        // DPL email on the mahasiswa endpoint and vice versa
        $this->put('user/insert', ['createuser' => [$mhs->email, $dpl->email]])->assertOk()->assertJsonStructure(['error']);
        $this->put('user/insertuser', ['createuser' => [$dpl->email, $mhs->email]])->assertOk()->assertJsonStructure(['error']);
        $this->put('user/insert', ['createuser' => ['bukan-email']])->assertOk()->assertJsonStructure(['error']);

        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_bulk_rejects_incomplete_source_records(): void
    {
        $this->loginAs('admin');
        $valid = $this->mahasiswa();
        $validDpl = $this->dpl();
        $badMahasiswa = [
            $this->mahasiswa(['nim' => '']),
            $this->mahasiswa(['nim' => '   ']),
            $this->mahasiswa(['nama' => '']),
            $this->mahasiswa(['nama' => null]),
            $this->mahasiswa(['kodept' => '999999']),
            $this->mahasiswa(['kodept' => null]),
        ];
        $badDpl = [
            $this->dpl(['nidn' => '']),
            $this->dpl(['nama' => ' ']),
            $this->dpl(['kodept' => '999999']),
        ];

        foreach ($badMahasiswa as $bad) {
            $this->put('user/insert', ['createuser' => [$valid->email, $bad->email]])
                ->assertOk()->assertJsonPath('error', fn ($error) => str_contains($error, mb_strtolower($bad->email)));
        }
        foreach ($badDpl as $bad) {
            $this->put('user/insertuser', ['createuser' => [$validDpl->email, $bad->email]])
                ->assertOk()->assertJsonPath('error', fn ($error) => str_contains($error, mb_strtolower($bad->email)));
        }

        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_bulk_rejects_duplicate_source_emails_including_case_and_spaces(): void
    {
        $this->loginAs('admin');
        $pairs = [
            ['dup1@pps.test', 'DUP1@pps.test'],
            ['dup2@pps.test', ' dup2@pps.test'],
            ['dup3@pps.test', 'dup3@pps.test '],
            ['dup4@pps.test', 'dup4@pps.test'],
        ];

        foreach ($pairs as [$a, $b]) {
            $this->mahasiswa(['email' => $a]);
            $this->mahasiswa(['email' => $b]);
            $this->dpl(['email' => 'd-'.$a]);
            $this->dpl(['email' => 'd-'.ltrim($b)]);

            $this->put('user/insert', ['createuser' => [$a]])->assertOk()->assertJsonStructure(['error']);
            $this->put('user/insertuser', ['createuser' => ['d-'.$a]])->assertOk()->assertJsonStructure(['error']);
        }

        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_bulk_rejects_email_present_in_other_role_table(): void
    {
        $this->loginAs('admin');
        $mhs = $this->mahasiswa(['email' => 'silang@pps.test']);
        $this->dpl(['email' => 'SILANG@pps.test']);
        $dpl = $this->dpl(['email' => 'silang2@pps.test']);
        $this->mahasiswa(['email' => ' silang2@pps.test']);

        $this->put('user/insert', ['createuser' => [$mhs->email]])->assertOk()->assertJsonStructure(['error']);
        $this->put('user/insertuser', ['createuser' => ['SILANG@pps.test']])->assertOk()->assertJsonStructure(['error']);
        $this->put('user/insertuser', ['createuser' => [$dpl->email]])->assertOk()->assertJsonStructure(['error']);

        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_bulk_error_message_escapes_email(): void
    {
        $this->loginAs('admin');
        $payload = '"<img src=x onerror=alert(1)>"@pps.test';

        $response = $this->put('user/insert', ['createuser' => [$payload]])->assertOk();

        $error = $response->json('error');
        $this->assertIsString($error);
        $this->assertStringNotContainsString('<img', $error);
        $this->assertStringContainsString('&lt;img', $error);
    }

    public function test_bulk_normalizes_input_and_stores_source_email_exactly(): void
    {
        $this->loginAs('admin');
        $mhs = $this->mahasiswa(['email' => 'Andi.Mixed@pps.test']);

        $this->put('user/insert', ['createuser' => ['  andi.mixed@PPS.test ', 'ANDI.MIXED@pps.test']])
            ->assertOk()->assertJson(['success' => '1 user berhasil dibuat']);

        $this->assertSame(['Andi.Mixed@pps.test'], User::where('role', 'mahasiswa')->pluck('email')->all());
        $this->assertSame($mhs->email, Mahasiswa::find($mhs->id_mahasiswa)->user->email);
    }

    // ---- Relasi 1:1 ----

    public function test_one_source_record_never_gets_two_users(): void
    {
        $this->loginAs('admin');
        $mhs = $this->mahasiswa(['email' => 'satu@pps.test']);
        $dpl = $this->dpl(['email' => 'dosen@pps.test']);
        // Legacy kepala account already holds a mahasiswa email
        $taken = $this->mahasiswa(['email' => 'dipakai@pps.test']);
        User::factory()->role('kepala')->create(['email' => 'dipakai@pps.test']);

        foreach (['satu@pps.test', 'SATU@pps.test', ' satu@pps.test '] as $variant) {
            $this->put('user/insert', ['createuser' => [$variant]])->assertOk()->assertJsonMissingPath('error');
        }
        foreach (['dosen@pps.test', 'Dosen@PPS.test'] as $variant) {
            $this->put('user/insertuser', ['createuser' => [$variant]])->assertOk()->assertJsonMissingPath('error');
        }
        $this->put('user/insert', ['createuser' => [$taken->email]])->assertOk()->assertJson(['success' => '0 user berhasil dibuat']);

        $this->assertSame(1, User::where('email', $mhs->email)->count());
        $this->assertSame(1, User::where('email', $dpl->email)->count());
        $this->assertSame('kepala', User::where('email', $taken->email)->value('role'));
        $this->assertLessThanOrEqual(Mahasiswa::count(), User::where('role', 'mahasiswa')->count());
        $this->assertLessThanOrEqual(Dpl::count(), User::where('role', 'dpl')->count());
    }

    public function test_user_count_per_role_never_exceeds_source_records(): void
    {
        $this->loginAs('admin');
        $mahasiswa = collect(range(1, 3))->map(fn () => $this->mahasiswa());
        $dpl = collect(range(1, 2))->map(fn () => $this->dpl());
        $mhsEmails = $mahasiswa->pluck('email')->all();
        $dplEmails = $dpl->pluck('email')->all();

        $this->put('user/insert', ['createuser' => $mhsEmails])->assertJson(['success' => '3 user berhasil dibuat']);
        $this->put('user/insert', ['createuser' => array_map('strtoupper', $mhsEmails)])->assertJson(['success' => '0 user berhasil dibuat']);
        $this->put('user/insertuser', ['createuser' => $dplEmails])->assertJson(['success' => '2 user berhasil dibuat']);
        $this->put('user/insertuser', ['createuser' => array_merge($dplEmails, $dplEmails)])->assertJson(['success' => '0 user berhasil dibuat']);

        $this->assertSame(Mahasiswa::count(), User::where('role', 'mahasiswa')->count());
        $this->assertSame(Dpl::count(), User::where('role', 'dpl')->count());
    }

    // ---- Update user (mahasiswa/dpl) ----

    public function test_update_user_cannot_take_email_of_another_record(): void
    {
        $this->loginAs('admin');
        $user = $this->linkedUser('mahasiswa', 'pemilik@pps.test');
        $dplUser = $this->linkedUser('dpl', 'dosen.pemilik@pps.test');
        $otherMhs = $this->mahasiswa(['email' => 'lain@pps.test']);
        $otherDpl = $this->dpl(['email' => 'dosen.lain@pps.test']);

        foreach ([$otherMhs->email, 'LAIN@pps.test', ' lain@pps.test ', $otherDpl->email, $dplUser->email] as $email) {
            $this->put('user/updateuser', ['id' => $user->id, 'name' => 'X', 'email' => $email])
                ->assertOk()->assertJsonValidationErrors('email', 'errors');
        }
        $this->put('user/updateuser', ['id' => $dplUser->id, 'name' => 'X', 'email' => $otherMhs->email])
            ->assertJsonValidationErrors('email', 'errors');

        $this->assertSame('pemilik@pps.test', $user->fresh()->email);
        $this->assertSame('dosen.pemilik@pps.test', $dplUser->fresh()->email);
        $this->assertDatabaseHas('mahasiswa', ['email' => 'pemilik@pps.test']);
        $this->assertSame(1, Mahasiswa::where('email', 'lain@pps.test')->count());
    }

    // Same normalization as CreateAccountsRequest (LOWER(TRIM)): a leading-space record is the same person
    public function test_update_user_cannot_take_email_of_record_differing_by_leading_space(): void
    {
        $this->loginAs('admin');
        $user = $this->linkedUser('mahasiswa', 'pemilik@pps.test');
        $this->mahasiswa(['email' => ' lain@pps.test']);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'X', 'email' => 'lain@pps.test'])
            ->assertJsonValidationErrors('email', 'errors');

        $this->assertSame('pemilik@pps.test', $user->fresh()->email);
    }

    public function test_orphan_user_cannot_change_email_but_can_edit_name(): void
    {
        $this->loginAs('admin');
        $orphan = User::factory()->role('dpl')->create(['email' => 'yatim@pps.test']);

        $this->put('user/updateuser', ['id' => $orphan->id, 'name' => 'X', 'email' => 'baru@pps.test'])
            ->assertOk()->assertJsonValidationErrors('email', 'errors');
        $this->put('user/updateuser', ['id' => $orphan->id, 'name' => 'Nama Baru', 'email' => 'yatim@pps.test'])
            ->assertJson(['success' => true]);

        $this->assertSame('yatim@pps.test', $orphan->fresh()->email);
        $this->assertSame('Nama Baru', $orphan->fresh()->name);
    }

    public function test_case_only_email_change_keeps_stored_email(): void
    {
        $this->loginAs('admin');
        $user = $this->linkedUser('mahasiswa', 'Budi.Case@pps.test');

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Budi', 'email' => '  budi.case@PPS.TEST '])
            ->assertJson(['success' => true]);

        $this->assertSame('Budi.Case@pps.test', $user->fresh()->email);
        $this->assertSame('Budi.Case@pps.test', Mahasiswa::where('email', 'Budi.Case@pps.test')->value('email'));
    }

    public function test_linked_user_can_change_to_free_email(): void
    {
        $this->loginAs('admin');
        $user = $this->linkedUser('dpl', 'dosen.lama@pps.test');

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Dosen', 'email' => ' Dosen.Baru@pps.test'])
            ->assertJson(['success' => true]);

        $this->assertSame('dosen.baru@pps.test', $user->fresh()->email);
        $this->assertDatabaseHas('dpl', ['email' => 'dosen.baru@pps.test']);
    }

    // ---- Kepala/pemda ----

    public function test_kepala_edit_without_email_change_is_allowed_despite_source_email_clash(): void
    {
        $this->loginAs('admin');
        foreach (['kepala' => fn () => $this->mahasiswa(['email' => 'bentrok@pps.test']), 'pemda' => fn () => $this->dpl(['email' => 'bentrok2@pps.test'])] as $role => $source) {
            $record = $source();
            $user = User::factory()->role($role)->create(['email' => $record->email]);

            $this->put('user/updateuserkepala', ['id' => $user->id, 'role' => $role, 'name' => 'Nama Baru', 'email' => $record->email])
                ->assertOk()->assertJson(['success' => true]);
            $this->assertSame('Nama Baru', $user->fresh()->name);
        }
    }

    public function test_kepala_cannot_take_mahasiswa_or_dpl_email(): void
    {
        $this->loginAs('admin');
        $kepala = User::factory()->role('kepala')->create(['email' => 'kepala@pps.test']);
        $mhs = $this->mahasiswa(['email' => 'mhs.slot@pps.test']);
        $dpl = $this->dpl(['email' => 'dpl.slot@pps.test']);

        foreach ([$mhs->email, $dpl->email, 'MHS.SLOT@pps.test'] as $email) {
            $this->put('user/updateuserkepala', ['id' => $kepala->id, 'role' => 'kepala', 'name' => 'K', 'email' => $email])
                ->assertJsonValidationErrors('email', 'errors');
            $this->put('user/insertuserkepala', ['role' => 'pemda', 'name' => 'P', 'email' => $email, 'password' => 'rahasia123'])
                ->assertJsonValidationErrors('email', 'errors');
        }

        $this->assertSame('kepala@pps.test', $kepala->fresh()->email);
        $this->assertSame(0, User::where('role', 'pemda')->count());
    }

    // ---- listdataserver ----

    public function test_listdataserver_caps_page_length(): void
    {
        $this->loginAs('admin');
        User::factory()->role('kepala')->count(105)->create();

        foreach (['-1' => 100, '5000' => 100, '0' => 100, '25' => 25] as $length => $expected) {
            $response = $this->listServer(['length' => $length])->assertOk();
            $this->assertCount($expected, $response->json('data'), "length={$length}");
            $this->assertSame(105, $response->json('recordsTotal'));
        }
    }

    public function test_listdataserver_search_treats_wildcards_literally(): void
    {
        $this->loginAs('admin');
        User::factory()->role('kepala')->create(['name' => 'Budi_Santoso', 'email' => 'a_b@pps.test']);
        User::factory()->role('kepala')->create(['name' => 'BudiXSantoso', 'email' => 'axb@pps.test']);
        User::factory()->role('pemda')->create(['name' => 'Diskon 50% Pemda', 'email' => 'p1@pps.test']);
        User::factory()->role('pemda')->create(['name' => 'Diskon 501 Pemda', 'email' => 'p2@pps.test']);

        $this->assertSame(['a_b@pps.test'], $this->searchEmails('i_S'));
        $this->assertSame(['a_b@pps.test'], $this->searchEmails('a_b@'));
        $this->assertSame(['p1@pps.test'], $this->searchEmails('50%'));
        $this->assertSame(['p1@pps.test'], $this->searchEmails('%'));
        $this->assertSame([], $this->searchEmails('\\'));
    }

    public function test_listdataserver_search_matches_nim_and_pt_name(): void
    {
        $this->loginAs('admin');
        $user = $this->linkedUser('mahasiswa', 'nim.cari@pps.test', ['nim' => '10_777']);
        $this->linkedUser('mahasiswa', 'nim.lain@pps.test', ['nim' => '10x777']);

        $this->assertSame([$user->email], $this->searchEmails('10_7'));
        $this->assertCount(2, $this->searchEmails($this->sp->nm_lemb));
    }

    public function test_listdataserver_invalid_filter_returns_json_422(): void
    {
        $this->loginAs('admin');

        foreach (['role' => 'admin', 'kodept' => "x' OR 1=1"] as $key => $value) {
            $this->listServer([$key => $value])
                ->assertStatus(422)->assertJson(['success' => false])->assertJsonValidationErrors($key, 'errors');
            $this->getJson('user/listdata?'.http_build_query([$key => $value]), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertStatus(422)->assertJsonValidationErrors($key, 'errors');
        }
        $this->listServer(['role' => ['mahasiswa']])->assertStatus(422);
    }

    // ---- Export ----

    public function test_export_q_filters_like_table(): void
    {
        $this->loginAs('admin');
        $andi = $this->linkedUser('mahasiswa', 'andi@pps.test', ['nim' => '10999001'], ['name' => 'Andi Pratama']);
        $this->linkedUser('dpl', 'dosen.budi@pps.test', [], ['name' => 'Budi Dosen']);
        User::factory()->role('kepala')->create(['name' => 'Andi Kepala', 'email' => 'kepala.andi@pps.test']);

        $exported = [];
        foreach (['Pratama andi', '10999001', 'andi'] as $keyword) {
            $exported[$keyword] = $this->exportEmails('user/export?q='.urlencode($keyword));
            $this->assertSame($this->searchEmails($keyword), $exported[$keyword], "q={$keyword}");
        }
        $this->assertSame([$andi->email], $exported['Pratama andi']);
        $this->assertSame([$andi->email], $exported['10999001']);
    }

    public function test_export_q_escapes_wildcards_and_combines_with_filters(): void
    {
        $this->loginAs('admin');
        User::factory()->role('kepala')->create(['name' => 'Budi_Santoso', 'email' => 'a_b@pps.test']);
        User::factory()->role('kepala')->create(['name' => 'BudiXSantoso', 'email' => 'axb@pps.test']);
        User::factory()->role('pemda')->create(['name' => 'Budi_Pemda', 'email' => 'pemda_b@pps.test']);

        $this->assertSame(['a_b@pps.test', 'pemda_b@pps.test'], $this->exportEmails('user/export?q=i_'));
        $this->assertSame(['a_b@pps.test'], $this->exportEmails('user/export?role=kepala&q=i_'));
        $this->get('user/export?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    // ---- Authorization ----

    public function test_non_admin_cannot_access_user_management_endpoints(): void
    {
        $mhs = $this->mahasiswa();
        $dpl = $this->dpl();
        $target = $this->linkedUser('mahasiswa', 'target@pps.test');
        $kepala = User::factory()->role('kepala')->create(['email' => 'kepala.target@pps.test']);

        foreach (['dpl', 'pt', 'kepala', 'pemda', 'mahasiswa'] as $role) {
            $this->loginAs($role);

            $this->assertDenied($this->put('user/insert', ['createuser' => [$mhs->email]]), $role);
            $this->assertDenied($this->put('user/insertuser', ['createuser' => [$dpl->email]]), $role);
            $this->assertDenied($this->put('user/updateuser', ['id' => $target->id, 'name' => 'Hack', 'email' => 'hack@pps.test']), $role);
            $this->assertDenied($this->put('user/insertuserkepala', ['role' => 'kepala', 'name' => 'Hack', 'email' => 'hack2@pps.test', 'password' => 'rahasia123']), $role);
            $this->assertDenied($this->put('user/updateuserkepala', ['id' => $kepala->id, 'role' => 'pemda', 'name' => 'Hack', 'email' => 'hack3@pps.test']), $role);
            $this->assertDenied($this->listServer(), $role);
            $this->assertDenied($this->get('user/export?q=a'), $role);
        }

        $this->assertNull($mhs->user()->first());
        $this->assertNull($dpl->user()->first());
        $this->assertSame('target@pps.test', $target->fresh()->email);
        $this->assertSame('kepala', $kepala->fresh()->role);
        $this->assertSame(0, User::whereIn('email', ['hack@pps.test', 'hack2@pps.test', 'hack3@pps.test'])->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $mhs = $this->mahasiswa();

        $this->put('user/insert', ['createuser' => [$mhs->email]])->assertRedirect(route('login'));
        $this->get('user/export')->assertRedirect(route('login'));
        $this->assertNull($mhs->user()->first());
    }

    // Role middleware redirects home; kepala/pemda are stopped earlier with 403 (read-only roles)
    private function assertDenied(TestResponse $response, string $role): void
    {
        if ($response->status() === 403) {
            return;
        }
        $this->assertTrue($response->isRedirect(route('home')), "{$role}: expected 403 or redirect home, got {$response->status()}");
    }

    private function mahasiswa(array $attributes = []): Mahasiswa
    {
        return Mahasiswa::factory()->create($attributes + ['kodept' => $this->sp->npsn]);
    }

    private function dpl(array $attributes = []): Dpl
    {
        return Dpl::factory()->create($attributes + ['kodept' => $this->sp->npsn]);
    }

    private function linkedUser(string $role, string $email, array $source = [], array $user = []): User
    {
        $role === 'dpl' ? $this->dpl(['email' => $email] + $source) : $this->mahasiswa(['email' => $email] + $source);

        return User::factory()->role($role)->create(['email' => $email] + $user);
    }

    private function listServer(array $params = []): TestResponse
    {
        $columns = collect(['name', 'email', 'id_number', 'pt_name', 'role'])
            ->map(fn ($c) => ['data' => $c, 'name' => $c, 'searchable' => 'true', 'orderable' => 'true'])->all();

        return $this->getJson('user/listdataserver?'.http_build_query($params + [
            'draw' => 1, 'start' => 0, 'length' => 100, 'columns' => $columns,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function searchEmails(string $keyword): array
    {
        return collect($this->listServer(['search' => ['value' => $keyword, 'regex' => 'false']])->assertOk()->json('data'))
            ->pluck('email')->sort()->values()->all();
    }

    private function exportEmails(string $uri): array
    {
        $path = tempnam(sys_get_temp_dir(), 'users_export');
        try {
            file_put_contents($path, $this->get($uri)->assertOk()->streamedContent());
            $reader = new Reader;
            $reader->open($path);
            $emails = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    if ($i > XlsxStreamer::HEADER_ROW) {
                        $emails[] = $row->getCells()[3]->getValue();
                    }
                }
            }
            $reader->close();
        } finally {
            unlink($path);
        }
        sort($emails);

        return $emails;
    }
}
