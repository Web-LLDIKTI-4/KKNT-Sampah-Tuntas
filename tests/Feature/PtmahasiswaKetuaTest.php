<?php

namespace Tests\Feature;

use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PtmahasiswaKetuaTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ptA = Satuanpendidikan::factory()->create();
        $this->ptB = Satuanpendidikan::factory()->create();
    }

    private function mahasiswa(Satuanpendidikan $pt, ?Desa $desa = null, bool $akun = true, array $attrs = []): Mahasiswa
    {
        $mahasiswa = Mahasiswa::factory()->create($attrs + ['kodept' => $pt->npsn]);
        if ($akun) {
            User::factory()->role('mahasiswa')->create(['email' => $mahasiswa->email, 'name' => $mahasiswa->nama]);
        }
        if ($desa) {
            Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mahasiswa->id_mahasiswa, 'id_desa' => $desa->id_desa]);
        }

        return $mahasiswa;
    }

    private function ketua(Mahasiswa $mahasiswa, ?Desa $desa = null): Pjdesa
    {
        User::where('email', $mahasiswa->email)->update(['akses' => 'pjdesa']);

        return Pjdesa::create(['email' => $mahasiswa->email, 'id_desa' => ($desa ?? Desa::factory()->create())->id_desa])->refresh();
    }

    private function loginPtA(): User
    {
        return $this->loginAs('pt', ['email' => $this->ptA->npsn]);
    }

    private function set(array $payload)
    {
        return $this->putJson('ptmahasiswa/setketua', $payload, self::AJAX);
    }

    private function cabut(array $payload)
    {
        return $this->putJson('ptmahasiswa/cabutketua', $payload, self::AJAX);
    }

    private function list(int $length = 50)
    {
        return $this->getJson('ptmahasiswa/listdataserver?draw=1&start=0&length='.$length, self::AJAX);
    }

    private function rowOf(array $rows, Mahasiswa $mahasiswa): array
    {
        foreach ($rows as $row) {
            if ($row['nim'] === $mahasiswa->nim) {
                return $row;
            }
        }
        $this->fail('Baris '.$mahasiswa->nim.' tidak ditemukan');
    }

    private function capaianPayload(): array
    {
        return [
            'bulan' => now()->toDateString(),
            'status_capaian' => 'P',
            'tautan' => 'https://drive.google.com/x',
            'permasalahan' => 'Masalah',
            'solusi' => 'Solusi',
            'kendala' => 'Kendala',
        ];
    }

    // AC: set sukses -> pj_desa baru, desa lokasi, status belum, akses pjdesa, role tetap, bisa isi capaian
    public function test_pt_sets_ketua_and_ketua_can_fill_capaian(): void
    {
        $desa = Desa::factory()->create();
        $mhs = $this->mahasiswa($this->ptA, $desa);
        $this->loginPtA();

        $this->set(['id_mahasiswa' => $mhs->id_mahasiswa])->assertOk()
            ->assertJson(['success' => true, 'message' => 'Ketua kelompok berhasil ditetapkan']);

        $pj = Pjdesa::where('email', $mhs->email)->sole();
        $this->assertSame($desa->id_desa, $pj->id_desa);
        $this->assertSame('belum', $pj->status_mobilisasi);
        $user = User::where('email', $mhs->email)->sole();
        $this->assertSame('pjdesa', $user->akses);
        $this->assertSame('mahasiswa', $user->role);

        // Muncul di daftar Ketua Kelompok PT
        $res = $this->getJson('ptpjdesa/listdataserver?draw=1&start=0&length=10', self::AJAX)->assertOk();
        $this->assertSame([$mhs->nama], array_column($res->json('data'), 'pjdesa'));
        $this->assertStringContainsString('Belum', $res->json('data.0.mobilisasi'));

        // Ketua bisa mengisi evaluasi pendataan sampah
        $this->actingAs($user);
        $this->put('capaiankegiatan/insert', $this->capaianPayload())->assertOk()->assertJson(['success' => true]);
        $this->assertSame($pj->id_pjdesa, CapaianKegiatan::where('email', $mhs->email)->sole()->id_pjdesa);
    }

    // AC: desa dari lokasi terbaru; id_desa/email/role dari request diabaikan
    public function test_desa_from_latest_lokasi_and_extra_fields_ignored(): void
    {
        $lama = Desa::factory()->create();
        $baru = Desa::factory()->create();
        $manual = Desa::factory()->create();
        $mhs = $this->mahasiswa($this->ptA, null, true, ['nama' => 'Nama Asli']);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y') - 1, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $lama->id_desa]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $baru->id_desa]);
        $lain = $this->ketua($this->mahasiswa($this->ptA, Desa::factory()->create()));
        $pt = $this->loginPtA();

        $this->set([
            'id_mahasiswa' => $mhs->id_mahasiswa,
            'id_desa' => $manual->id_desa,
            'email' => 'penyusup@x.test',
            'role' => 'admin',
            'nama' => 'Diubah',
            'id_pjdesa' => $lain->id_pjdesa,
            'status_mobilisasi' => 'sudah',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame($baru->id_desa, Pjdesa::where('email', $mhs->email)->sole()->id_desa);
        $this->assertDatabaseMissing('pj_desa', ['email' => 'penyusup@x.test']);
        $this->assertSame('Nama Asli', $mhs->fresh()->nama);
        $this->assertSame('mahasiswa', User::where('email', $mhs->email)->value('role'));
        $this->assertSame('pt', $pt->fresh()->role);
        $this->assertSame('belum', $lain->fresh()->status_mobilisasi);
    }

    // AC: tolak belum lokasi / belum akun / sudah ketua, tanpa data baru
    public function test_set_rejected_for_rule_violations(): void
    {
        $tanpaLokasi = $this->mahasiswa($this->ptA);
        $tanpaAkun = $this->mahasiswa($this->ptA, Desa::factory()->create(), false);
        $sudahKetua = $this->mahasiswa($this->ptA, Desa::factory()->create());
        $this->ketua($sudahKetua);
        $this->loginPtA();

        $this->set(['id_mahasiswa' => $tanpaLokasi->id_mahasiswa])->assertOk()
            ->assertJson(['success' => false])->assertJsonFragment(['message' => 'Tidak dapat set sebagai ketua kelompok, mahasiswa harus set lokasi kegiatan KKN terlebih dahulu!']);
        $this->set(['id_mahasiswa' => $tanpaAkun->id_mahasiswa])->assertOk()
            ->assertJson(['success' => false])->assertJsonFragment(['message' => 'Mahasiswa belum memiliki akun login, tidak dapat ditetapkan sebagai ketua kelompok.']);
        $this->set(['id_mahasiswa' => $sudahKetua->id_mahasiswa])->assertOk()
            ->assertJson(['success' => false])->assertJsonFragment(['message' => 'Mahasiswa sudah menjadi ketua kelompok.']);

        $this->assertSame(1, Pjdesa::count());
        $this->assertDatabaseMissing('pj_desa', ['email' => $tanpaLokasi->email]);
        $this->assertDatabaseMissing('pj_desa', ['email' => $tanpaAkun->email]);
        $this->assertNull(User::where('email', $tanpaLokasi->email)->value('akses'));
    }

    // Keputusan Q1: bebas, 2 ketua satu desa OK
    public function test_two_ketua_in_same_desa_allowed(): void
    {
        $desa = Desa::factory()->create();
        $a = $this->mahasiswa($this->ptA, $desa);
        $b = $this->mahasiswa($this->ptA, $desa);
        $this->loginPtA();

        $this->set(['id_mahasiswa' => $a->id_mahasiswa])->assertJson(['success' => true]);
        $this->set(['id_mahasiswa' => $b->id_mahasiswa])->assertJson(['success' => true]);

        $this->assertSame(2, Pjdesa::where('id_desa', $desa->id_desa)->count());
    }

    // Anti-IDOR: mahasiswa PT lain / id tidak dikenal -> 404
    public function test_set_for_other_pt_or_unknown_is_404(): void
    {
        $milikB = $this->mahasiswa($this->ptB, Desa::factory()->create());
        $this->loginPtA();

        $this->set(['id_mahasiswa' => $milikB->id_mahasiswa])->assertNotFound()->assertJson(['success' => false]);
        $this->set(['id_mahasiswa' => (string) Str::uuid()])->assertNotFound();

        $res = $this->set(['id_mahasiswa' => 'bukan-uuid']);
        $this->assertArrayHasKey('id_mahasiswa', (array) $res->json('errors'));
        $this->assertFalse($res->json('success'));

        $this->assertSame(0, Pjdesa::count());
        $this->assertNull(User::where('email', $milikB->email)->value('akses'));
    }

    // AC: cabut sukses (termasuk ketua tunjukan admin) -> baris hilang, akses null, capaian ditolak
    public function test_pt_revokes_ketua_and_capaian_is_forbidden_after(): void
    {
        $mhs = $this->mahasiswa($this->ptA, Desa::factory()->create());
        $pj = $this->ketua($mhs);
        $this->loginPtA();

        $this->cabut(['id_pjdesa' => $pj->id_pjdesa])->assertOk()
            ->assertJson(['success' => true, 'message' => 'Akses ketua kelompok berhasil dicabut']);

        $this->assertDatabaseMissing('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
        $user = User::where('email', $mhs->email)->sole();
        $this->assertNull($user->akses);
        $this->assertSame('mahasiswa', $user->role);

        $this->actingAs($user);
        $this->put('capaiankegiatan/insert', $this->capaianPayload())->assertForbidden();
        $this->assertSame(0, CapaianKegiatan::count());
    }

    // AC: cabut ditolak bila ada evaluasi pendataan sampah tahun ini
    public function test_revoke_rejected_when_capaian_this_year(): void
    {
        $mhs = $this->mahasiswa($this->ptA, Desa::factory()->create());
        $pj = $this->ketua($mhs);
        CapaianKegiatan::factory()->create([
            'email' => $mhs->email,
            'id_pjdesa' => $pj->id_pjdesa,
            'bulan' => now()->startOfMonth()->toDateString(),
        ]);
        $this->loginPtA();

        $res = $this->cabut(['id_pjdesa' => $pj->id_pjdesa])->assertOk()->assertJson(['success' => false]);
        $this->assertStringContainsString('ketua kelompok memiliki evaluasi pendataan sampah tahun ini', $res->json('message') ?? $res->json('error'));

        $this->assertDatabaseHas('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
        $this->assertSame('pjdesa', User::where('email', $mhs->email)->value('akses'));
    }

    // Anti-IDOR: ketua PT lain / id tidak dikenal -> 404
    public function test_revoke_other_pt_ketua_or_unknown_is_404(): void
    {
        $pjB = $this->ketua($this->mahasiswa($this->ptB, Desa::factory()->create()));
        $this->loginPtA();

        $this->cabut(['id_pjdesa' => $pjB->id_pjdesa])->assertNotFound();
        $this->cabut(['id_pjdesa' => (string) Str::uuid()])->assertNotFound();
        $res = $this->cabut(['id_pjdesa' => 'bukan-uuid']);
        $this->assertArrayHasKey('id_pjdesa', (array) $res->json('errors'));

        $this->assertDatabaseHas('pj_desa', ['id_pjdesa' => $pjB->id_pjdesa]);
        $this->assertSame('pjdesa', User::where('email', $pjB->email)->value('akses'));
    }

    // AC: role selain PT ditolak di 2 route (redirect role / 403 read-only)
    public function test_non_pt_roles_are_rejected(): void
    {
        $mhs = $this->mahasiswa($this->ptA, Desa::factory()->create());
        $pj = $this->ketua($this->mahasiswa($this->ptA, Desa::factory()->create()));

        foreach (['admin', 'kepala', 'pemda', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $set = $this->put('ptmahasiswa/setketua', ['id_mahasiswa' => $mhs->id_mahasiswa], self::AJAX)->getStatusCode();
            $cabut = $this->put('ptmahasiswa/cabutketua', ['id_pjdesa' => $pj->id_pjdesa], self::AJAX)->getStatusCode();
            $this->assertContains($set, [302, 403], $role.' setketua');
            $this->assertContains($cabut, [302, 403], $role.' cabutketua');
            auth()->logout();
        }

        $this->put('ptmahasiswa/setketua', ['id_mahasiswa' => $mhs->id_mahasiswa])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('pj_desa', ['email' => $mhs->email]);
        $this->assertDatabaseHas('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
    }

    // AC: rate limit sensitive (10/menit per user)
    public function test_set_and_cabut_are_throttled(): void
    {
        $this->loginPtA();
        $unknown = ['id_mahasiswa' => (string) Str::uuid()];

        for ($i = 0; $i < 10; $i++) {
            $this->set($unknown)->assertNotFound();
        }
        $this->set($unknown)->assertStatus(429);
        // Limiter "sensitive" dibagi per user
        $this->cabut(['id_pjdesa' => (string) Str::uuid()])->assertStatus(429);
    }

    // AC: kolom ketua_kelompok (boolean, ikon via boolIcon) + action untuk PT, escape nama/desa
    public function test_pt_listdataserver_columns_and_escaping(): void
    {
        config(['app.debug' => false]);
        $xss = 'Ani <b>"x';
        $desaXss = Desa::factory()->create(['desa' => 'Desa <b>"y']);
        $ketuaMhs = $this->mahasiswa($this->ptA, $desaXss, true, ['nama' => 'Ketua <b>"k']);
        $pj = $this->ketua($ketuaMhs, $desaXss);
        $calon = $this->mahasiswa($this->ptA, Desa::factory()->create(), true, ['nama' => $xss]);
        $tanpaLokasi = $this->mahasiswa($this->ptA);
        $tanpaAkun = $this->mahasiswa($this->ptA, Desa::factory()->create(), false);
        $this->mahasiswa($this->ptB, Desa::factory()->create());
        $this->loginPtA();

        $res = $this->list()->assertOk()->assertJsonPath('recordsTotal', 4);
        $rows = $res->json('data');

        $k = $this->rowOf($rows, $ketuaMhs);
        $this->assertTrue($k['ketua_kelompok']);
        $this->assertArrayNotHasKey('ketua_status', $k);
        $this->assertStringContainsString('Cabut Ketua', $k['action']);
        $this->assertStringContainsString('data-id-value="'.$pj->id_pjdesa.'"', $k['action']);
        $this->assertStringContainsString(route('ptmahasiswa.cabutketua'), $k['action']);
        $this->assertStringNotContainsString('Jadikan Ketua', $k['action']);

        $c = $this->rowOf($rows, $calon);
        $this->assertFalse($c['ketua_kelompok']);
        $this->assertArrayNotHasKey('ketua_status', $c);
        $this->assertStringContainsString('Jadikan Ketua', $c['action']);
        $this->assertStringContainsString('btn-confirm-put', $c['action']);
        $this->assertStringContainsString('data-id-value="'.$calon->id_mahasiswa.'"', $c['action']);
        $this->assertStringContainsString('Jadikan '.e($xss).' ketua', $c['action']);

        $this->assertSame('-', trim($this->rowOf($rows, $tanpaLokasi)['action']));
        $this->assertSame('-', trim($this->rowOf($rows, $tanpaAkun)['action']));

        $this->assertStringNotContainsString('<b>', $res->getContent());
        $this->assertStringNotContainsString('<b>"', implode('', array_column($rows, 'action')));
    }

    // AC: kepala/pemda tidak mendapat kolom action, tetap dapat ketua_kelompok
    public function test_kepala_and_pemda_have_no_action_column(): void
    {
        $this->ketua($this->mahasiswa($this->ptA, Desa::factory()->create()));
        $this->mahasiswa($this->ptA, Desa::factory()->create());

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $rows = $this->list()->assertOk()->json('data');
            $this->assertNotEmpty($rows, $role);
            foreach ($rows as $row) {
                $this->assertArrayNotHasKey('action', $row, $role);
                $this->assertArrayNotHasKey('punya_akun', $row, $role);
                $this->assertIsBool($row['ketua_kelompok'], $role);
                $this->assertArrayNotHasKey('ketua_status', $row, $role);
            }

            $html = $this->get('ptmahasiswa/listdata')->assertOk()->getContent();
            $this->assertStringNotContainsString('<th>Aksi</th>', $html, $role);
            $this->assertStringNotContainsString('ptmahasiswa/setketua', $html, $role);
            auth()->logout();
        }
    }

    // N+1 guard: jumlah query tidak naik per baris
    public function test_listdataserver_query_count_constant_per_row(): void
    {
        $this->loginPtA();
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->list()->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $this->ketua($this->mahasiswa($this->ptA, Desa::factory()->create()));
        $this->mahasiswa($this->ptA, Desa::factory()->create());
        $small = $count();

        for ($i = 0; $i < 4; $i++) {
            $this->ketua($this->mahasiswa($this->ptA, Desa::factory()->create()));
            $this->mahasiswa($this->ptA, Desa::factory()->create());
            $this->mahasiswa($this->ptA, null, false);
        }
        $this->assertSame(14, count($this->list()->json('data')));
        $this->assertSame($small, $count());
    }

    // Regresi: setelah set & cabut via PT, admin pjdesa list konsisten
    public function test_ketua_set_by_pt_visible_to_admin_and_mobilisasi_editable(): void
    {
        $mhs = $this->mahasiswa($this->ptA, Desa::factory()->create());
        $this->loginPtA();
        $this->set(['id_mahasiswa' => $mhs->id_mahasiswa])->assertJson(['success' => true]);
        $pj = Pjdesa::where('email', $mhs->email)->sole();

        $this->putJson('ptpjdesa/update', ['id_pjdesa' => $pj->id_pjdesa, 'status_mobilisasi' => 'sudah'], self::AJAX)
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame('sudah', $pj->fresh()->status_mobilisasi);
    }
}
