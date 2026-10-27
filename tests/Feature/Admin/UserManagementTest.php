<?php

namespace Tests\Feature\Admin;

use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Freeform;
use App\Models\Kehadiran;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Nilaikonversi;
use App\Models\PenguranganSampah;
use App\Models\Pjdesa;
use App\Models\RealisasiKerja;
use App\Models\RencanaKerja;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bulk_creates_accounts_with_correct_roles(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        $mhs = Mahasiswa::factory()->create(['kodept' => $sp->npsn]);
        $dpl = Dpl::factory()->create(['kodept' => $sp->npsn]);

        $this->put('user/insert', ['createuser' => [$mhs->email]])->assertJson(['success' => '1 user berhasil dibuat']);
        $this->put('user/insertuser', ['createuser' => [$dpl->email]])->assertJson(['success' => '1 user berhasil dibuat']);
        $this->put('user/insert', ['createuser' => [$mhs->email]])->assertJson(['success' => '0 user berhasil dibuat']);

        $this->assertSame('mahasiswa', User::where('email', $mhs->email)->value('role'));
        $this->assertSame('dpl', User::where('email', $dpl->email)->value('role'));
        $this->assertTrue(Hash::check($mhs->nim, User::where('email', $mhs->email)->value('password')));
    }

    public function test_pt_account_role_cannot_be_escalated(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();

        $this->put('user/insertuserpt', [
            'name' => 'PT X', 'kodept' => $sp->npsn, 'location_program' => LokasiProgram::factory()->create()->id,
            'phone' => '0812 3456', 'password' => 'Rahasia123', 'role' => 'admin',
        ])->assertJson(['success' => true]);

        $this->assertSame('pt', User::where('email', $sp->npsn)->value('role'));
    }

    public function test_update_user_rejects_admin_target_and_ignores_role_input(): void
    {
        $admin = $this->loginAs('admin');
        $target = User::factory()->role('mahasiswa')->create();

        $this->put('user/updateuser', ['id' => $admin->id, 'name' => 'x', 'email' => 'x@pps.test'])
            ->assertJsonValidationErrors('id', 'errors');
        $this->put('user/updateuser', ['id' => $target->id, 'name' => 'x', 'email' => $target->email, 'role' => 'admin'])
            ->assertJson(['success' => true]);
        $this->assertSame('mahasiswa', $target->fresh()->role);
    }

    public function test_update_mahasiswa_without_akses_keeps_existing_akses(): void
    {
        $this->loginAs('admin');
        $target = User::factory()->role('mahasiswa')->create(['akses' => 'pjdesa']);

        $this->put('user/updateuser', ['id' => $target->id, 'name' => 'Nama Baru', 'email' => $target->email, 'akses' => ''])
            ->assertJson(['success' => true]);

        $this->assertSame('Nama Baru', $target->fresh()->name);
        $this->assertSame('pjdesa', $target->fresh()->akses);
    }

    public function test_email_change_cascades_to_related_data(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('mahasiswa')->create(['email' => 'lama@pps.test']);
        Mahasiswa::factory()->create(['email' => 'lama@pps.test']);
        Logkegiatan::factory()->create(['email' => 'lama@pps.test']);
        Dplmentoring::create(['email_mahasiswa' => 'lama@pps.test', 'email_dpl' => 'dpl@pps.test']);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Baru', 'email' => 'baru@pps.test'])
            ->assertJson(['success' => true]);

        $this->assertSame('baru@pps.test', $user->fresh()->email);
        $this->assertDatabaseHas('mahasiswa', ['email' => 'baru@pps.test']);
        $this->assertDatabaseHas('logkegiatan', ['email' => 'baru@pps.test']);
        $this->assertDatabaseHas('dpl_mentoring', ['email_mahasiswa' => 'baru@pps.test']);
        $this->assertDatabaseMissing('logkegiatan', ['email' => 'lama@pps.test']);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $this->loginAs('dpl');

        $this->put('user/insertuserpt', ['name' => 'x'])->assertRedirect(route('home'));
    }

    public function test_admin_deletes_mahasiswa_with_all_related_data(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('mahasiswa')->create(['image' => 'foto.jpg']);
        $mhs = Mahasiswa::factory()->create(['email' => $user->email]);
        Storage::disk('local')->put('photo/foto.jpg', 'x');
        Logkegiatan::factory()->create(['email' => $user->email]);
        Kehadiran::factory()->create(['email' => $user->email]);
        Nilaikonversi::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        Mahasiswa_lokasi::create(['tahun' => 2026, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa]);
        Dplmentoring::create(['email_mahasiswa' => $user->email, 'email_dpl' => 'dpl@pps.test']);
        $this->sampah($user->email);
        $this->accountTraces($user);
        $other = Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

        foreach (['users', 'mahasiswa', 'logkegiatan', 'kehadiran', 'pengurangan_sampah', 'password_resets'] as $table) {
            $this->assertDatabaseMissing($table, ['email' => $user->email]);
        }
        $this->assertDatabaseMissing('nilai_konversi', ['id_mahasiswa' => $mhs->id_mahasiswa]);
        $this->assertDatabaseMissing('mahasiswa_lokasi', ['id_mahasiswa' => $mhs->id_mahasiswa]);
        $this->assertDatabaseMissing('dpl_mentoring', ['email_mahasiswa' => $user->email]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        Storage::disk('local')->assertMissing('photo/foto.jpg');
        $this->assertModelExists($other);
    }

    public function test_admin_deletes_mahasiswa_account_without_master_row(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('mahasiswa')->create();
        Logkegiatan::factory()->create(['email' => $user->email]);

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('logkegiatan', ['email' => $user->email]);
    }

    public function test_deleting_ketua_kelompok_removes_group_data(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('mahasiswa')->create(['akses' => 'pjdesa']);
        Mahasiswa::factory()->create(['email' => $user->email]);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        CapaianKegiatan::factory()->create(['email' => $user->email]);
        $this->sampah($user->email);
        $otherCapaian = CapaianKegiatan::factory()->create(['email' => 'ketua-lain@pps.test']);

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

        foreach (['pj_desa', 'capaian_kegiatan', 'pengurangan_sampah'] as $table) {
            $this->assertDatabaseMissing($table, ['email' => $user->email]);
        }
        $this->assertModelExists($otherCapaian);
    }

    public function test_deleting_dpl_detaches_grades_and_final_assignment(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('dpl')->create();
        $dpl = Dpl::factory()->create(['email' => $user->email]);
        $nilai = Nilaikonversi::factory()->create(['email_dpl' => $user->email]);
        $freeform = Freeform::factory()->create(['email_dpl' => $user->email]);
        $ta = Tugasakhir::factory()->create(['email' => 'mhs@pps.test', 'email_dpl' => $user->email]);
        Dplmentoring::create(['email_mahasiswa' => 'mhs@pps.test', 'email_dpl' => $user->email]);
        Dpllaporan::factory()->create(['email' => $user->email]);

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

        $this->assertModelMissing($user);
        $this->assertModelMissing($dpl);
        $this->assertNull($nilai->fresh()->email_dpl);
        $this->assertNull($freeform->fresh()->email_dpl);
        $this->assertNull($ta->fresh()->email_dpl);
        $this->assertDatabaseMissing('dpl_mentoring', ['email_dpl' => $user->email]);
        $this->assertDatabaseMissing('dpl_laporan_bulanan', ['email' => $user->email]);
    }

    public function test_deleting_pt_removes_rencana_kerja_and_evaluation_answers(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        $user = User::factory()->role('pt')->create(['email' => $sp->npsn]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $sp->npsn]);
        $rencana = RencanaKerja::factory()->forPt($sp->npsn)->create(['uploaded_by' => $user->id]);
        Storage::disk(RencanaKerja::DISK)->put($rencana->file_path, 'pdf');
        Evaluasikegiatanjawaban::create(['user' => $sp->npsn, 'kodept' => $sp->npsn, 'jawaban' => 'ok', 'tahun' => 2026]);

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

        $this->assertModelMissing($user);
        $this->assertModelMissing($rencana);
        Storage::disk(RencanaKerja::DISK)->assertMissing($rencana->file_path);
        $this->assertDatabaseMissing('evaluasi_kegiatan_jawaban', ['user' => $sp->npsn]);
        $this->assertModelExists($sp);
        $this->assertModelExists($mhs);
    }

    public function test_deleting_pt_removes_realisasi_kerja_and_files(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        $otherSp = Satuanpendidikan::factory()->create();
        $user = User::factory()->role('pt')->create(['email' => $sp->npsn]);
        $realisasi = RealisasiKerja::factory()->forPt($sp->npsn)->count(2)->create(['uploaded_by' => $user->id]);
        $otherRealisasi = RealisasiKerja::factory()->forPt($otherSp->npsn)->create();
        foreach ([...$realisasi, $otherRealisasi] as $row) {
            Storage::disk(RealisasiKerja::DISK)->put($row->file_path, 'pdf');
        }

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

        $this->assertModelMissing($user);
        foreach ($realisasi as $row) {
            $this->assertModelMissing($row);
            Storage::disk(RealisasiKerja::DISK)->assertMissing($row->file_path);
        }
        $this->assertModelExists($otherRealisasi);
        Storage::disk(RealisasiKerja::DISK)->assertExists($otherRealisasi->file_path);
        $this->assertModelExists($sp);
    }

    public function test_failed_pt_deletion_keeps_realisasi_kerja_and_files(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        $user = User::factory()->role('pt')->create(['email' => $sp->npsn]);
        $realisasi = RealisasiKerja::factory()->forPt($sp->npsn)->create();
        Storage::disk(RealisasiKerja::DISK)->put($realisasi->file_path, 'pdf');
        DB::listen(function ($query) {
            if (preg_match('/^delete from [`"]users[`"]/', $query->sql)) {
                throw new RuntimeException('boom');
            }
        });

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => false]);

        $this->assertModelExists($user);
        $this->assertModelExists($realisasi);
        Storage::disk(RealisasiKerja::DISK)->assertExists($realisasi->file_path);
    }

    public function test_deleting_kepala_and_pemda_removes_account_only(): void
    {
        $this->loginAs('admin');

        foreach (['kepala', 'pemda'] as $role) {
            $user = User::factory()->role($role)->create();
            $this->accountTraces($user);

            $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => true]);

            $this->assertModelMissing($user);
            $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
            $this->assertDatabaseMissing('password_resets', ['email' => $user->email]);
        }
    }

    public function test_destroy_rejects_admin_target_and_self(): void
    {
        $admin = $this->loginAs('admin');
        $otherAdmin = User::factory()->role('admin')->create();

        $this->put('user/destroy', ['id' => $otherAdmin->id])->assertJsonValidationErrors('id', 'errors');
        $this->put('user/destroy', ['id' => $admin->id])->assertForbidden();
        $this->put('user/destroy', ['id' => 'bukan-uuid'])->assertJsonValidationErrors('id', 'errors');

        $this->assertModelExists($otherAdmin);
        $this->assertModelExists($admin);
    }

    public function test_non_admin_cannot_delete_users(): void
    {
        $target = User::factory()->role('mahasiswa')->create();
        $this->loginAs('pt');

        $this->put('user/destroy', ['id' => $target->id])->assertRedirect(route('home'));

        $this->assertModelExists($target);
    }

    public function test_non_admin_cannot_filter_or_export_users(): void
    {
        $sp = Satuanpendidikan::factory()->create();

        foreach (['dpl', 'pt'] as $role) {
            $this->loginAs($role);

            $this->get('user/listdata?role=mahasiswa')->assertRedirect(route('home'));
            $this->get('user/export?kodept='.$sp->npsn)->assertRedirect(route('home'));
        }
    }

    public function test_failed_deletion_rolls_back_and_keeps_files(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        $user = User::factory()->role('pt')->create(['email' => $sp->npsn, 'image' => 'pt.jpg']);
        $rencana = RencanaKerja::factory()->forPt($sp->npsn)->create();
        Storage::disk(RencanaKerja::DISK)->put($rencana->file_path, 'pdf');
        Storage::disk('local')->put('photo/pt.jpg', 'x');
        // Gagalkan langkah terakhir transaksi
        DB::listen(function ($query) {
            if (preg_match('/^delete from [`"]users[`"]/', $query->sql)) {
                throw new RuntimeException('boom');
            }
        });

        $this->put('user/destroy', ['id' => $user->id])->assertJson(['success' => false, 'message' => 'Data gagal dihapus']);

        $this->assertModelExists($user);
        $this->assertModelExists($rencana);
        Storage::disk(RencanaKerja::DISK)->assertExists($rencana->file_path);
        Storage::disk('local')->assertExists('photo/pt.jpg');
    }

    public function test_listdata_filters_by_role_and_perguruan_tinggi(): void
    {
        $this->loginAs('admin');
        [$sp, $users] = $this->usersAcrossPt();

        $emails = fn (string $query) => collect($this->getJson('user/listdataserver?draw=1&start=0&length=50'.$query, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'))->pluck('email')->sort()->values()->all();
        $expected = fn (array $list) => collect($list)->pluck('email')->sort()->values()->all();

        $this->get('user/listdata?role=dpl')->assertOk()->assertViewHas('filters', ['role' => 'dpl']);
        $this->assertCount(6, $emails(''));
        $this->assertSame($expected([$users['dpl'], $users['dplLain']]), $emails('&role=dpl'));
        $this->assertSame($expected([$users['mhs'], $users['dpl'], $users['pt']]), $emails('&kodept='.$sp->npsn));
        $this->assertSame([$users['mhs']->email], $emails('&role=mahasiswa&kodept='.$sp->npsn));
        $this->assertSame([], $emails('&role=kepala&kodept='.$sp->npsn));
    }

    public function test_listdata_rejects_invalid_filter(): void
    {
        $this->loginAs('admin');

        $this->get('user/listdata?role=admin')->assertSessionHasErrors('role');
        $this->get('user/listdata?kodept='.urlencode("x' OR 1=1"))->assertSessionHasErrors('kodept');
    }

    public function test_export_follows_filter(): void
    {
        $this->loginAs('admin');
        [$sp, $users] = $this->usersAcrossPt();

        $rows = $this->exportedRows('user/export?kodept='.$sp->npsn);
        $header = array_shift($rows);
        $this->assertSame(['No.', 'Nama', 'NIM/NIDN/Kode PT', 'Email', 'Perguruan Tinggi', 'Peran'], array_map(fn ($cell) => $cell->getValue(), $header));
        $this->assertSame(
            collect([$users['mhs'], $users['dpl'], $users['pt']])->pluck('email')->sort()->values()->all(),
            collect($rows)->map(fn ($row) => $row[3]->getValue())->sort()->values()->all()
        );
        $this->assertSame([$sp->nm_lemb], collect($rows)->map(fn ($row) => $row[4]->getValue())->unique()->values()->all());
        $this->assertSame([1, 2, 3], collect($rows)->map(fn ($row) => $row[0]->getValue())->all());

        $this->assertCount(7, $this->exportedRows('user/export'));
    }

    public function test_export_writes_formula_like_text_as_plain_string(): void
    {
        $this->loginAs('admin');
        $payload = '=HYPERLINK("http://evil.test","x")';
        User::factory()->role('kepala')->create(['name' => $payload]);

        $path = tempnam(sys_get_temp_dir(), 'users_export');
        try {
            file_put_contents($path, $this->get('user/export?role=kepala')->assertOk()->streamedContent());
            $zip = new ZipArchive;
            $zip->open($path);
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
        } finally {
            unlink($path);
        }

        // Reader re-wraps "=" values as formulas, so inspect the raw sheet XML instead
        $this->assertStringContainsString('t="inlineStr"><is><t>'.htmlspecialchars($payload, ENT_QUOTES | ENT_XML1), $sheet);
        $this->assertStringNotContainsString('<f>', $sheet);
    }

    public function test_index_lists_perguruan_tinggi_with_users(): void
    {
        $this->loginAs('admin');
        [$sp] = $this->usersAcrossPt();
        $unused = Satuanpendidikan::factory()->create();

        $this->get('user')->assertOk()->assertViewHas('perguruanTinggi', fn ($pt) => $pt->has($sp->npsn) && ! $pt->has($unused->npsn));
    }

    private function sampah(string $email): void
    {
        PenguranganSampah::create([
            'email' => $email, 'id_desa' => Desa::factory()->create()->id_desa, 'bulan' => '2026-09-01',
            'jml_rw' => 1, 'jml_penduduk' => 10, 'jml_rumah' => 3, 'jml_rumah_memilah' => 1,
            'timbulan' => 5, 'organik_sumber' => 1, 'organik_dlh' => 1, 'anorganik_sumber' => 1,
            'pengurangan' => 2, 'belum_terkelola' => 3,
        ]);
    }

    private function accountTraces(User $user): void
    {
        DB::table('sessions')->insert(['id' => 'sess-'.$user->id, 'user_id' => $user->id, 'payload' => 'x', 'last_activity' => time()]);
        DB::table('password_resets')->insert(['email' => $user->email, 'token' => 'hash', 'created_at' => now()]);
    }

    // Download the streamed XLSX and read every row back as cells
    private function exportedRows(string $uri): array
    {
        $response = $this->get($uri)->assertOk();
        $this->assertMatchesRegularExpression('/filename=users_.+\.xlsx$/', $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'users_export');
        try {
            file_put_contents($path, $response->streamedContent());
            $reader = new Reader;
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    if ($i === 1) {
                        $this->assertSame('Data Pengguna', $row->getCells()[0]->getValue());
                    }
                    // Title and info rows sit above the header
                    if ($i >= XlsxStreamer::HEADER_ROW) {
                        $rows[] = $row->getCells();
                    }
                }
            }
            $reader->close();
        } finally {
            unlink($path);
        }

        return $rows;
    }

    // Users in two PTs plus one kepala (admin login excluded)
    private function usersAcrossPt(): array
    {
        $sp = Satuanpendidikan::factory()->create();
        $spLain = Satuanpendidikan::factory()->create();
        $users = [];
        foreach (['mhs' => [$sp, 'mahasiswa'], 'mhsLain' => [$spLain, 'mahasiswa'], 'dpl' => [$sp, 'dpl'], 'dplLain' => [$spLain, 'dpl']] as $key => [$pt, $role]) {
            $users[$key] = User::factory()->role($role)->create();
            ($role === 'mahasiswa' ? Mahasiswa::factory() : Dpl::factory())->create(['email' => $users[$key]->email, 'kodept' => $pt->npsn]);
        }
        $users['pt'] = User::factory()->role('pt')->create(['email' => $sp->npsn]);
        User::factory()->role('kepala')->create();

        return [$sp, $users];
    }
}
