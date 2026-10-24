<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\DestroyPersonRequest;
use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Kehadiran;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Nilaikonversi;
use App\Models\PendataanPemilahanSampah;
use App\Models\PenguranganSampah;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private const MODULES = ['user', 'mahasiswa', 'dpl'];

    // ---- Edit massal ----

    public function test_bulk_update_mahasiswa_changes_only_sent_fields_and_syncs_users(): void
    {
        $this->loginAs('admin');
        $ptAsal = Satuanpendidikan::factory()->create();
        $ptTujuan = Satuanpendidikan::factory()->create();
        $lokasi = LokasiProgram::factory()->create();
        $a = Mahasiswa::factory()->create(['kodept' => $ptAsal->npsn, 'prodi' => 'TI', 'tahun_masuk' => 2022]);
        $b = Mahasiswa::factory()->create(['kodept' => $ptAsal->npsn, 'prodi' => 'SI', 'tahun_masuk' => 2023]);
        $untouched = Mahasiswa::factory()->create(['kodept' => $ptAsal->npsn]);
        $userA = User::factory()->role('mahasiswa')->create(['email' => $a->email]);

        $this->put('mahasiswa/bulk-update', [
            'ids' => [$a->id_mahasiswa, $b->id_mahasiswa],
            'kodept' => $ptTujuan->npsn,
            'location_program' => $lokasi->id,
            'prodi' => '',
            'tahun_masuk' => '',
        ])->assertOk()->assertJson(['success' => true, 'toast' => 'success', 'processed' => 2, 'skipped' => 0, 'failed' => 0]);

        $this->assertSame([$ptTujuan->npsn, 'TI', 2022, $lokasi->id], [$a->fresh()->kodept, $a->fresh()->prodi, (int) $a->fresh()->tahun_masuk, $a->fresh()->location_program]);
        $this->assertSame([$ptTujuan->npsn, 'SI', 2023], [$b->fresh()->kodept, $b->fresh()->prodi, (int) $b->fresh()->tahun_masuk]);
        $this->assertSame($ptAsal->npsn, $untouched->fresh()->kodept);
        $this->assertSame($lokasi->id, $userA->fresh()->location_program);
    }

    public function test_bulk_update_dpl_changes_prodi_and_reports_missing_ids(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['prodi' => 'TI', 'kodept' => '041001']);
        $missing = (string) Str::uuid();

        $this->put('dpl/bulk-update', ['ids' => [$dpl->id_dpl, $missing], 'prodi' => 'Manajemen'])
            ->assertJson(['success' => true, 'toast' => 'warning', 'processed' => 1, 'skipped' => 1])
            ->assertJsonPath('skipped_items.0.id', $missing);

        $this->assertSame(['Manajemen', '041001'], [$dpl->fresh()->prodi, $dpl->fresh()->kodept]);
    }

    public function test_bulk_update_without_any_field_is_rejected(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create(['prodi' => 'TI']);
        $dpl = Dpl::factory()->create(['prodi' => 'TI']);
        $user = User::factory()->role('mahasiswa')->create(['location_program' => null]);

        $this->put('mahasiswa/bulk-update', ['ids' => [$mhs->id_mahasiswa], 'prodi' => '', 'kodept' => ''])
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.location_program.0', 'Pilih minimal satu field untuk diubah.');
        // tahun_masuk bukan field DPL, jadi tetap dianggap kosong
        $this->put('dpl/bulk-update', ['ids' => [$dpl->id_dpl], 'tahun_masuk' => 2024])
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.location_program.0', 'Pilih minimal satu field untuk diubah.');
        $this->put('user/bulk-update', ['ids' => [$user->id]])
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.location_program.0', 'Pilih minimal satu field untuk diubah.');

        $this->assertSame('TI', $mhs->fresh()->prodi);
        $this->assertSame('TI', $dpl->fresh()->prodi);
        $this->assertNull($user->fresh()->location_program);
    }

    public function test_bulk_update_invalid_value_changes_nothing(): void
    {
        $this->loginAs('admin');
        $a = Mahasiswa::factory()->create(['prodi' => 'TI', 'tahun_masuk' => 2022]);

        foreach ([['tahun_masuk' => 1800], ['kodept' => 'TIDAKADA'], ['location_program' => (string) Str::uuid()], ['location_program' => 'bukan-uuid']] as $invalid) {
            $this->put('mahasiswa/bulk-update', ['ids' => [$a->id_mahasiswa], 'prodi' => 'Baru'] + $invalid)
                ->assertJson(['success' => false]);
        }

        $this->assertSame(['TI', 2022], [$a->fresh()->prodi, (int) $a->fresh()->tahun_masuk]);
    }

    public function test_bulk_update_rejects_nim_collision_at_target_pt(): void
    {
        $this->loginAs('admin');
        $asal = Satuanpendidikan::factory()->create();
        $tujuan = Satuanpendidikan::factory()->create();
        $moving = Mahasiswa::factory()->create(['nim' => '1111', 'kodept' => $asal->npsn]);
        $other = Mahasiswa::factory()->create(['nim' => '2222', 'kodept' => $asal->npsn]);
        Mahasiswa::factory()->create(['nim' => '1111', 'kodept' => $tujuan->npsn]);

        $this->put('mahasiswa/bulk-update', ['ids' => [$moving->id_mahasiswa, $other->id_mahasiswa], 'kodept' => $tujuan->npsn])
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.kodept.0', 'NIM sudah terdaftar di PT tujuan: 1111.');

        // Semua atau tidak sama sekali: baris tanpa bentrok juga tidak pindah
        $this->assertSame($asal->npsn, $moving->fresh()->kodept);
        $this->assertSame($asal->npsn, $other->fresh()->kodept);
    }

    public function test_bulk_update_rejects_duplicate_identity_among_selection(): void
    {
        $this->loginAs('admin');
        $tujuan = Satuanpendidikan::factory()->create();
        $mA = Mahasiswa::factory()->create(['nim' => '3333', 'kodept' => '041001']);
        $mB = Mahasiswa::factory()->create(['nim' => '3333', 'kodept' => '041002']);
        $dA = Dpl::factory()->create(['nidn' => '0401', 'kodept' => '041001']);
        $dB = Dpl::factory()->create(['nidn' => '0401', 'kodept' => '041002']);

        $this->put('mahasiswa/bulk-update', ['ids' => [$mA->id_mahasiswa, $mB->id_mahasiswa], 'kodept' => $tujuan->npsn])
            ->assertJsonPath('errors.kodept.0', 'NIM ganda di antara data terpilih: 3333.');
        $this->put('dpl/bulk-update', ['ids' => [$dA->id_dpl, $dB->id_dpl], 'kodept' => $tujuan->npsn])
            ->assertJsonPath('errors.kodept.0', 'NIDN ganda di antara data terpilih: 0401.');

        $this->assertSame('041001', $mA->fresh()->kodept);
        $this->assertSame('041001', $dA->fresh()->kodept);
    }

    public function test_bulk_update_rejects_nidn_collision_at_target_pt(): void
    {
        $this->loginAs('admin');
        $tujuan = Satuanpendidikan::factory()->create();
        $dpl = Dpl::factory()->create(['nidn' => '0499', 'kodept' => '041001']);
        Dpl::factory()->create(['nidn' => '0499', 'kodept' => $tujuan->npsn]);

        $this->put('dpl/bulk-update', ['ids' => [$dpl->id_dpl], 'kodept' => $tujuan->npsn])
            ->assertJsonPath('errors.kodept.0', 'NIDN sudah terdaftar di PT tujuan: 0499.');

        $this->assertSame('041001', $dpl->fresh()->kodept);
    }

    public function test_bulk_update_user_lokasi_skips_roles_without_lokasi(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create();
        $mhsUser = User::factory()->role('mahasiswa')->create();
        $mhs = Mahasiswa::factory()->create(['email' => $mhsUser->email]);
        $dplUser = User::factory()->role('dpl')->create();
        $dpl = Dpl::factory()->create(['email' => $dplUser->email]);
        $kepala = User::factory()->role('kepala')->create(['location_program' => null]);
        $missing = (string) Str::uuid();

        $this->put('user/bulk-update', ['ids' => [$mhsUser->id, $dplUser->id, $kepala->id, $missing], 'location_program' => $lokasi->id])
            ->assertJson(['success' => true, 'toast' => 'warning', 'processed' => 2, 'skipped' => 2, 'failed' => 0]);

        $this->assertSame($lokasi->id, $mhsUser->fresh()->location_program);
        $this->assertSame($lokasi->id, $mhs->fresh()->location_program);
        $this->assertSame($lokasi->id, $dpl->fresh()->location_program);
        $this->assertNull($kepala->fresh()->location_program);
    }

    public function test_bulk_update_with_uppercase_ids_counts_once(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create(['prodi' => 'TI']);

        $this->put('mahasiswa/bulk-update', ['ids' => [strtoupper($mhs->id_mahasiswa)], 'prodi' => 'SI'])
            ->assertJson(['success' => true, 'processed' => 1, 'skipped' => 0]);

        $this->assertSame('SI', $mhs->fresh()->prodi);
    }

    public function test_bulk_edit_forms_render_only_editable_fields(): void
    {
        $this->loginAs('admin');

        $this->get('mahasiswa/bulk-edit')->assertOk()->assertSee('-- Tidak diubah --')
            ->assertSee('name="tahun_masuk"', false)->assertSee('name="kodept"', false)->assertDontSee('name="nim"', false);
        $this->get('dpl/bulk-edit')->assertOk()->assertSee('name="prodi"', false)->assertDontSee('name="tahun_masuk"', false)->assertDontSee('name="nidn"', false);
        $this->get('user/bulk-edit')->assertOk()->assertSee('name="location_program"', false)->assertDontSee('name="role"', false);
    }

    // ---- Hapus massal ----

    public function test_bulk_destroy_users_skips_admin_and_self_with_correct_counts(): void
    {
        $admin = $this->loginAs('admin');
        $otherAdmin = User::factory()->role('admin')->create();
        $mhs = User::factory()->role('mahasiswa')->create();
        $kepala = User::factory()->role('kepala')->create();
        $missing = (string) Str::uuid();

        $response = $this->put('user/bulk-destroy', ['ids' => [$admin->id, $otherAdmin->id, $mhs->id, $kepala->id, $missing]])
            ->assertJson(['success' => true, 'toast' => 'warning', 'processed' => 2, 'skipped' => 3, 'failed' => 0, 'message' => '2 data diproses, 3 dilewati.']);

        $reasons = collect($response->json('skipped_items'))->pluck('reason', 'id');
        $this->assertSame('Akun sendiri tidak dapat dihapus', $reasons[$admin->id]);
        $this->assertSame('Akun admin tidak dapat dihapus', $reasons[$otherAdmin->id]);
        $this->assertSame('Data tidak ditemukan / sudah terhapus', $reasons[$missing]);
        $this->assertModelExists($admin);
        $this->assertModelExists($otherAdmin);
        $this->assertModelMissing($mhs);
        $this->assertModelMissing($kepala);
    }

    public function test_bulk_destroy_only_admins_returns_error_toast(): void
    {
        $admin = $this->loginAs('admin');

        $this->put('user/bulk-destroy', ['ids' => [$admin->id]])
            ->assertJson(['success' => true, 'toast' => 'error', 'processed' => 0, 'skipped' => 1]);
    }

    public function test_bulk_destroy_accepts_uppercase_ids(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        $dpl = Dpl::factory()->create();
        $user = User::factory()->role('kepala')->create();

        $this->put('mahasiswa/bulk-destroy', ['ids' => [strtoupper($mhs->id_mahasiswa)]])->assertJson(['processed' => 1, 'skipped' => 0]);
        $this->put('dpl/bulk-destroy', ['ids' => [strtoupper($dpl->id_dpl)]])->assertJson(['processed' => 1, 'skipped' => 0]);
        $this->put('user/bulk-destroy', ['ids' => [strtoupper($user->id)]])->assertJson(['processed' => 1, 'skipped' => 0]);

        $this->assertModelMissing($mhs);
        $this->assertModelMissing($dpl);
        $this->assertModelMissing($user);
    }

    public function test_bulk_destroy_mahasiswa_removes_all_related_data(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('mahasiswa')->create(['image' => 'foto-bulk.jpg', 'akses' => 'pjdesa']);
        $mhs = Mahasiswa::factory()->create(['email' => $user->email]);
        $second = Mahasiswa::factory()->create();
        Storage::disk('local')->put('photo/foto-bulk.jpg', 'x');
        $email = $user->email;
        $desa = Desa::factory()->create();
        Logkegiatan::factory()->create(['email' => $email]);
        Kehadiran::factory()->create(['email' => $email]);
        PendataanPemilahanSampah::factory()->create(['email' => $email]);
        Logbulanan::factory()->create(['email' => $email]);
        Pjdesa::create(['email' => $email, 'id_desa' => $desa->id_desa]);
        CapaianKegiatan::factory()->create(['email' => $email]);
        Tugasakhir::factory()->create(['email' => $email]);
        Nilaikonversi::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        Freeform::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        Mahasiswa_lokasi::create(['tahun' => 2026, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa]);
        Dplmentoring::create(['email_mahasiswa' => $email, 'email_dpl' => 'dpl@pps.test']);
        PenguranganSampah::create([
            'email' => $email, 'id_desa' => $desa->id_desa, 'bulan' => '2026-09-01',
            'jml_rw' => 1, 'jml_penduduk' => 10, 'jml_rumah' => 3, 'jml_rumah_memilah' => 1,
            'timbulan' => 5, 'organik_sumber' => 1, 'organik_dlh' => 1, 'anorganik_sumber' => 1,
            'pengurangan' => 2, 'belum_terkelola' => 3,
        ]);
        DB::table('sessions')->insert(['id' => 'sess-'.$user->id, 'user_id' => $user->id, 'payload' => 'x', 'last_activity' => time()]);
        DB::table('password_resets')->insert(['email' => $email, 'token' => 'hash', 'created_at' => now()]);
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('kpi_capaian_dedup_backup'));
        DB::table('kpi_capaian_dedup_backup')->insert([
            ['id_capaian' => (string) Str::uuid(), 'email' => $email, 'bulan' => '2026-09-01'],
            ['id_capaian' => (string) Str::uuid(), 'email' => 'lain@pps.test', 'bulan' => '2026-09-01'],
        ]);
        $otherLog = Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $this->put('mahasiswa/bulk-destroy', ['ids' => [$mhs->id_mahasiswa, $second->id_mahasiswa]])
            ->assertJson(['success' => true, 'toast' => 'success', 'processed' => 2, 'skipped' => 0, 'failed' => 0]);

        foreach (['users', 'mahasiswa', 'logkegiatan', 'kehadiran', 'pendataan_pemilahan_sampah', 'logkegiatan_bulanan', 'pj_desa', 'capaian_kegiatan', 'pengurangan_sampah', 'tugasakhir', 'password_resets', 'kpi_capaian_dedup_backup'] as $table) {
            $this->assertDatabaseMissing($table, ['email' => $email]);
        }
        foreach (['nilai_konversi', 'nilai_freeform', 'mahasiswa_lokasi'] as $table) {
            $this->assertDatabaseMissing($table, ['id_mahasiswa' => $mhs->id_mahasiswa]);
        }
        $this->assertDatabaseMissing('dpl_mentoring', ['email_mahasiswa' => $email]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertModelMissing($second);
        Storage::disk('local')->assertMissing('photo/foto-bulk.jpg');
        $this->assertDatabaseHas('kpi_capaian_dedup_backup', ['email' => 'lain@pps.test']);
        $this->assertModelExists($otherLog);
    }

    public function test_single_destroy_mahasiswa_also_clears_dedup_backup(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        DB::table('kpi_capaian_dedup_backup')->insert(['id_capaian' => (string) Str::uuid(), 'email' => $mhs->email]);

        $this->put('mahasiswa/destroy', ['id_mahasiswa' => $mhs->id_mahasiswa])->assertJson(['success' => true]);

        $this->assertDatabaseMissing('kpi_capaian_dedup_backup', ['email' => $mhs->email]);
    }

    public function test_bulk_destroy_dpl_keeps_student_grades_and_detaches_dpl(): void
    {
        $this->loginAs('admin');
        $user = User::factory()->role('dpl')->create();
        $dpl = Dpl::factory()->create(['email' => $user->email]);
        $nilai = Nilaikonversi::factory()->create(['email_dpl' => $user->email]);
        $freeform = Freeform::factory()->create(['email_dpl' => $user->email]);
        $ta = Tugasakhir::factory()->create(['email' => 'mhs@pps.test', 'email_dpl' => $user->email, 'nilai_dpl' => 90]);
        Dplmentoring::create(['email_mahasiswa' => 'mhs@pps.test', 'email_dpl' => $user->email]);
        Dpllaporan::factory()->create(['email' => $user->email]);

        $this->put('dpl/bulk-destroy', ['ids' => [$dpl->id_dpl]])->assertJson(['success' => true, 'processed' => 1]);

        $this->assertModelMissing($dpl);
        $this->assertModelMissing($user);
        $this->assertModelExists($nilai);
        $this->assertModelExists($freeform);
        $this->assertModelExists($ta);
        $this->assertNull($nilai->fresh()->email_dpl);
        $this->assertNull($freeform->fresh()->email_dpl);
        $this->assertNull($ta->fresh()->email_dpl);
        $this->assertSame(90, $ta->fresh()->nilai_dpl);
        $this->assertDatabaseMissing('dpl_mentoring', ['email_dpl' => $user->email]);
        $this->assertDatabaseMissing('dpl_laporan_bulanan', ['email' => $user->email]);
    }

    public function test_bulk_destroy_failure_rolls_back_only_that_record(): void
    {
        $this->loginAs('admin');
        $ok = User::factory()->role('mahasiswa')->create();
        $broken = User::factory()->role('mahasiswa')->create();
        $brokenLog = Logkegiatan::factory()->create(['email' => $broken->email]);
        DB::listen(function ($query) use ($broken) {
            if (preg_match('/^delete from [`"]users[`"]/', $query->sql) && in_array($broken->id, $query->bindings, true)) {
                throw new RuntimeException('boom');
            }
        });

        $this->put('user/bulk-destroy', ['ids' => [$ok->id, $broken->id]])
            ->assertJson(['success' => true, 'toast' => 'warning', 'processed' => 1, 'skipped' => 0, 'failed' => 1])
            ->assertJsonPath('failed_items.0.id', $broken->id);

        $this->assertModelMissing($ok);
        $this->assertModelExists($broken);
        $this->assertModelExists($brokenLog);
    }

    public function test_bulk_rejects_more_than_500_ids_without_changes(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        $ids = [$mhs->id_mahasiswa, ...array_map(fn () => (string) Str::uuid(), range(1, 500))];

        $this->put('mahasiswa/bulk-destroy', ['ids' => $ids])
            ->assertJson(['success' => false, 'message' => 'Maksimal 500 data per aksi.']);
        $this->put('mahasiswa/bulk-update', ['ids' => $ids, 'prodi' => 'X'])->assertJson(['success' => false]);
        $this->post('mahasiswa/export-selected', ['ids' => $ids])->assertStatus(422)->assertJson(['message' => 'Maksimal 500 data per aksi.']);

        $this->assertModelExists($mhs);
        $this->assertNotSame('X', $mhs->fresh()->prodi);
    }

    public function test_bulk_rejects_invalid_ids(): void
    {
        $this->loginAs('admin');

        $this->put('user/bulk-destroy', [])->assertJson(['success' => false, 'message' => 'Pilih minimal satu data.']);
        $this->put('dpl/bulk-destroy', ['ids' => ['bukan-uuid']])->assertJson(['success' => false, 'message' => 'Data terpilih tidak valid.']);
        $id = (string) Str::uuid();
        $this->put('mahasiswa/bulk-destroy', ['ids' => [$id, $id]])->assertJson(['success' => false, 'message' => 'Data terpilih ganda.']);
    }

    // ---- Export terpilih ----

    public function test_export_selected_mahasiswa_follows_selection_order(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        $a = Mahasiswa::factory()->create(['nim' => '5001', 'kodept' => $sp->npsn]);
        $b = Mahasiswa::factory()->create(['nim' => '5002']);
        $c = Mahasiswa::factory()->create(['nim' => '5003']);

        $rows = $this->exportedRows('mahasiswa/export-selected', [$c->id_mahasiswa, $a->id_mahasiswa], 'Data Mahasiswa', 'mahasiswa_terpilih_');
        $header = array_shift($rows);

        $this->assertSame(
            ['No.', 'NIM', 'Nama', 'Email', 'HP', 'Perguruan Tinggi', 'Lokasi Program KKN', 'Status Ketua Kelompok', 'Status Set Lokasi', 'Kelurahan/Desa'],
            $header
        );
        $this->assertSame(['5003', '5001'], array_column($rows, 1));
        $this->assertSame($sp->nm_lemb, $rows[1][5]);
        $this->assertNotContains($b->email, array_column($rows, 3));
    }

    public function test_export_selected_dpl_and_user_follow_selection_order_without_secrets(): void
    {
        $this->loginAs('admin');
        $d1 = Dpl::factory()->create(['nidn' => '0411']);
        $d2 = Dpl::factory()->create(['nidn' => '0422']);
        $u1 = User::factory()->role('kepala')->create(['name' => 'Zeta']);
        $u2 = User::factory()->role('pemda')->create(['name' => 'Alpha']);

        $dplRows = $this->exportedRows('dpl/export-selected', [$d2->id_dpl, $d1->id_dpl], 'Data DPL', 'dpl_terpilih_');
        $this->assertSame(['No.', 'NIDN', 'Nama', 'Email', 'HP', 'Perguruan Tinggi', 'Lokasi Program KKN'], array_shift($dplRows));
        $this->assertSame(['0422', '0411'], array_column($dplRows, 1));

        $userRows = $this->exportedRows('user/export-selected', [strtoupper($u1->id), $u2->id], 'Data Pengguna', 'users_terpilih_');
        $header = array_shift($userRows);
        $this->assertSame(['No.', 'Nama', 'NIM/NIDN/Kode PT', 'Email', 'Perguruan Tinggi', 'Peran'], $header);
        $this->assertSame(['Zeta', 'Alpha'], array_column($userRows, 1));
        foreach ($header as $col) {
            $this->assertDoesNotMatchRegularExpression('/password|token/i', (string) $col);
        }
    }

    public function test_export_selected_validation_errors_return_422(): void
    {
        $this->loginAs('admin');

        $this->postJson('user/export-selected', [])->assertStatus(422)->assertJson(['success' => false, 'message' => 'Pilih minimal satu data.']);
        $this->postJson('dpl/export-selected', ['ids' => ['x']])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_export_selected_is_rate_limited(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->post('mahasiswa/export-selected', ['ids' => [$mhs->id_mahasiswa]])->assertOk();
        }
        $this->post('mahasiswa/export-selected', ['ids' => [$mhs->id_mahasiswa]])->assertStatus(429);
    }

    // ---- Akses ----

    public function test_non_admin_bulk_requests_are_redirected_and_change_nothing(): void
    {
        $mhs = Mahasiswa::factory()->create(['prodi' => 'TI']);
        $dpl = Dpl::factory()->create(['prodi' => 'TI']);
        $target = User::factory()->role('kepala')->create(['location_program' => null]);
        $lokasi = LokasiProgram::factory()->create();
        $ids = ['user' => $target->id, 'mahasiswa' => $mhs->id_mahasiswa, 'dpl' => $dpl->id_dpl];

        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            foreach (self::MODULES as $module) {
                $payload = ['ids' => [$ids[$module]], 'prodi' => 'Hack', 'location_program' => $lokasi->id];
                $this->get("$module/bulk-edit")->assertRedirect(route('home'));
                $this->put("$module/bulk-update", $payload)->assertRedirect(route('home'));
                $this->put("$module/bulk-destroy", $payload)->assertRedirect(route('home'));
                $this->post("$module/export-selected", $payload)->assertRedirect(route('home'));
            }
        }

        $this->assertSame('TI', $mhs->fresh()->prodi);
        $this->assertSame('TI', $dpl->fresh()->prodi);
        $this->assertNull($target->fresh()->location_program);
    }

    public function test_guest_bulk_requests_redirect_to_login(): void
    {
        $mhs = Mahasiswa::factory()->create();

        foreach (self::MODULES as $module) {
            $this->get("$module/bulk-edit")->assertRedirect(route('login'));
            $this->put("$module/bulk-destroy", ['ids' => [$mhs->id_mahasiswa]])->assertRedirect(route('login'));
            $this->post("$module/export-selected", ['ids' => [$mhs->id_mahasiswa]])->assertRedirect(route('login'));
        }

        $this->assertModelExists($mhs);
    }

    public function test_bulk_requests_authorize_admin_only(): void
    {
        foreach ([\App\Http\Requests\Admin\BulkSelectionRequest::class, \App\Http\Requests\Admin\BulkUpdateMahasiswaRequest::class, \App\Http\Requests\Admin\BulkUpdateDplRequest::class, \App\Http\Requests\Admin\BulkUpdateUserRequest::class, \App\Http\Requests\Admin\ExportSelectedRequest::class, DestroyPersonRequest::class] as $class) {
            foreach (['admin' => true, 'kepala' => false, 'pemda' => false, 'pt' => false, 'dpl' => false, 'mahasiswa' => false] as $role => $allowed) {
                $user = User::factory()->role($role)->make();
                $request = $class::create('x', 'PUT');
                $request->setUserResolver(fn () => $user);
                $this->assertSame($allowed, $request->authorize(), "$class / $role");
            }
            $guest = $class::create('x', 'PUT');
            $guest->setUserResolver(fn () => null);
            $this->assertFalse($guest->authorize(), "$class / guest");
        }
    }

    // ---- Hapus satuan ----

    public function test_single_destroy_validates_key_and_is_throttled(): void
    {
        $this->loginAs('admin');

        $this->put('mahasiswa/destroy', ['id_mahasiswa' => 'bukan-uuid'])->assertJsonValidationErrors('id_mahasiswa', 'errors');
        $this->put('dpl/destroy', [])->assertJsonValidationErrors('id_dpl', 'errors');

        foreach (['mahasiswa/destroy', 'dpl/destroy'] as $uri) {
            $middleware = Route::getRoutes()->match(request()->create($uri, 'PUT'))->gatherMiddleware();
            $this->assertContains('throttle:sensitive', $middleware, $uri);
        }

        // Limiter "sensitive" = 10/menit; 2 request di atas sudah terhitung
        foreach (range(3, 10) as $i) {
            $this->put('dpl/destroy', ['id_dpl' => (string) Str::uuid()])->assertOk();
        }
        $this->put('dpl/destroy', ['id_dpl' => (string) Str::uuid()])->assertStatus(429);
    }

    public function test_single_destroy_rejects_non_admin(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $this->loginAs('pt');

        $this->put('mahasiswa/destroy', ['id_mahasiswa' => $mhs->id_mahasiswa])->assertRedirect(route('home'));

        $this->assertModelExists($mhs);
    }

    // ---- UI / tabel ----

    public function test_selectable_table_and_toolbar_only_on_admin_lists(): void
    {
        $this->loginAs('admin');
        $this->get('mahasiswa/listdata')->assertOk()->assertSee('data-select-key="id_mahasiswa"', false)->assertSee('data-bulk-toolbar', false);
        $this->get('dpl/listdata')->assertOk()->assertSee('data-select-key="id_dpl"', false)->assertSee('data-bulk-toolbar', false);
        $this->get('user/listdata')->assertOk()->assertSee('data-select-key="id"', false)->assertSee('data-bulk-toolbar', false);
        // Tabel lain tanpa selectable tidak berubah
        $this->get('kategori-kegiatan/listdata')->assertOk()->assertDontSee('data-select-key', false)->assertDontSee('data-bulk-toolbar', false);

        $this->loginAs('pt', ['email' => Satuanpendidikan::factory()->create()->npsn]);
        $this->get('ptmahasiswa/listdata')->assertOk()->assertDontSee('data-select-key', false)->assertDontSee('data-bulk-toolbar', false);
    }

    public function test_listdataserver_accepts_checkbox_column_with_shifted_order(): void
    {
        $this->loginAs('admin');
        Mahasiswa::factory()->create(['nim' => '9002']);
        Mahasiswa::factory()->create(['nim' => '9001']);

        $query = http_build_query([
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [
                ['data' => '', 'name' => '', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'nim', 'name' => 'nim', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'order' => [['column' => 2, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        $this->getJson('mahasiswa/listdataserver?'.$query, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('data.0.nim', '9001');
    }

    // Download the xlsx and read rows from the header row on as plain values
    private function exportedRows(string $uri, array $ids, string $title, string $filePrefix): array
    {
        $response = $this->post($uri, ['ids' => $ids])->assertOk();
        $this->assertMatchesRegularExpression('/filename='.preg_quote($filePrefix, '/').'.+\.xlsx$/', $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'bulk_export');
        try {
            file_put_contents($path, $response->streamedContent());
            $reader = new Reader;
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    if ($i === 1) {
                        $this->assertSame($title, $row->getCells()[0]->getValue());
                    }
                    if ($i >= XlsxStreamer::HEADER_ROW) {
                        $rows[] = array_map(fn ($cell) => is_string($v = $cell->getValue()) ? $v : (string) $v, $row->getCells());
                    }
                }
            }
            $reader->close();
        } finally {
            unlink($path);
        }

        return $rows;
    }
}
