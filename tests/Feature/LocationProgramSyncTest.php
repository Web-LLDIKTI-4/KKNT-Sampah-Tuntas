<?php

namespace Tests\Feature;

use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\PetaSebaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocationProgramSyncTest extends TestCase
{
    use RefreshDatabase;

    private LokasiProgram $lama;

    private LokasiProgram $baru;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lama = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $this->baru = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Cimahi']);
    }

    private function mahasiswaPayload(Mahasiswa $mahasiswa, array $overrides = []): array
    {
        return $overrides + [
            'id_mahasiswa' => $mahasiswa->id_mahasiswa,
            'email' => $mahasiswa->email,
            'nim' => '2201234567',
            'tahun_masuk' => (int) date('Y') - 1,
            'nama' => 'Mahasiswa Baru',
            'phone' => '081299990000',
            'prodi' => 'Teknik Sipil',
            'kodept' => Satuanpendidikan::factory()->create()->npsn,
            'location_program' => $this->baru->id,
        ];
    }

    private function dplPayload(Dpl $dpl, array $overrides = []): array
    {
        return $overrides + [
            'id_dpl' => $dpl->id_dpl,
            'email' => $dpl->email,
            'nidn' => '0411223344',
            'nama' => 'Dr. Baru, M.T.',
            'phone' => '081299990000',
            'prodi' => 'Teknik Sipil',
            'kodept' => Satuanpendidikan::factory()->create()->npsn,
            'location_program' => $this->baru->id,
        ];
    }

    private function account(string $role, string $email, ?string $lokasiId): User
    {
        return User::factory()->role($role)->withPassword('Rahasia123')->create([
            'email' => $email,
            'location_program' => $lokasiId,
        ]);
    }

    private function login(string $email, string $lokasi)
    {
        Auth::logout();

        return $this->put('login', ['username' => $email, 'password' => 'Rahasia123', 'lokasi' => $lokasi]);
    }

    private function cacheVersion(): int
    {
        return (int) Cache::get(PetaSebaranService::VERSION_CACHE_KEY);
    }

    // AC1
    public function test_mahasiswa_edit_location_allows_login_with_new_location_only(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->lama->id]);
        $user = $this->account('mahasiswa', 'mhs@pps.test', $this->lama->id);

        $this->put('mahasiswa/update', $this->mahasiswaPayload($mahasiswa))->assertJson(['success' => true]);

        $this->assertSame($this->baru->id, $mahasiswa->fresh()->location_program);
        $this->assertSame($this->baru->id, $user->fresh()->location_program);

        $this->login('mhs@pps.test', 'Kota Cimahi')
            ->assertJson(['success' => true, 'redirect_url' => url('home/kota%20cimahi')]);
        $this->login('mhs@pps.test', 'Kota Bandung')
            ->assertJson(['success' => false, 'messages' => 'Lokasi program anda tidak valid!']);
        $this->assertGuest();
    }

    public function test_mahasiswa_edit_does_not_sync_location_to_non_mahasiswa_user_with_same_email(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'shared@pps.test', 'location_program' => $this->lama->id]);
        $dplUser = $this->account('dpl', 'shared@pps.test', $this->lama->id);

        $this->put('mahasiswa/update', $this->mahasiswaPayload($mahasiswa))->assertJson(['success' => true]);

        $this->assertSame($this->lama->id, $dplUser->fresh()->location_program);
    }

    // AC2
    public function test_dpl_edit_location_allows_login_with_new_location_only(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);

        $this->put('dpl/update', $this->dplPayload($dpl))->assertJson(['success' => true]);

        $this->assertSame($this->baru->id, $dpl->fresh()->location_program);
        $this->assertSame($this->baru->id, $user->fresh()->location_program);

        $this->login('dpl@pps.test', 'Kota Cimahi')
            ->assertJson(['success' => true, 'redirect_url' => url('home/kota%20cimahi')]);
        $this->login('dpl@pps.test', 'Kota Bandung')
            ->assertJson(['success' => false, 'messages' => 'Lokasi program anda tidak valid!']);
        $this->assertGuest();
    }

    public function test_dpl_edit_clearing_location_also_clears_account_location(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);

        $this->put('dpl/update', $this->dplPayload($dpl, ['location_program' => null]))->assertJson(['success' => true]);

        $this->assertNull($user->fresh()->location_program);
        $this->login('dpl@pps.test', 'Kota Bandung')->assertJson(['success' => false]);
    }

    public function test_dpl_edit_does_not_sync_location_to_non_dpl_user_with_same_email(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'shared@pps.test', 'location_program' => $this->lama->id]);
        $mhsUser = $this->account('mahasiswa', 'shared@pps.test', $this->lama->id);

        $this->put('dpl/update', $this->dplPayload($dpl))->assertJson(['success' => true]);

        $this->assertSame($this->lama->id, $mhsUser->fresh()->location_program);
    }

    // AC5 (DPL)
    public function test_dpl_edit_flushes_cache_only_when_location_or_kodept_changes(): void
    {
        $this->loginAs('admin');
        $kodept = Satuanpendidikan::factory()->create()->npsn;
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'kodept' => $kodept, 'location_program' => $this->lama->id]);
        $this->account('dpl', 'dpl@pps.test', $this->lama->id);
        Cache::forever(PetaSebaranService::VERSION_CACHE_KEY, 5);

        // Only non-cached columns (nama, nidn, ...) changed
        $this->put('dpl/update', $this->dplPayload($dpl, ['kodept' => $kodept, 'location_program' => $this->lama->id]))->assertJson(['success' => true]);
        $this->assertSame(5, $this->cacheVersion());

        // kodept feeds capaian/sebaran cache
        $this->put('dpl/update', $this->dplPayload($dpl, ['location_program' => $this->lama->id]))->assertJson(['success' => true]);
        $this->assertSame(6, $this->cacheVersion());

        $this->put('dpl/update', $this->dplPayload($dpl, ['kodept' => $dpl->fresh()->kodept]))->assertJson(['success' => true]);
        $this->assertSame(7, $this->cacheVersion());
    }

    // AC3
    public function test_edit_user_role_dpl_updates_dpl_location_only(): void
    {
        $this->loginAs('admin');
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Dr. Dpl', 'email' => 'dpl@pps.test', 'location_program' => $this->baru->id])
            ->assertJson(['success' => true]);

        $this->assertSame($this->baru->id, $user->fresh()->location_program);
        $this->assertSame($this->baru->id, $dpl->fresh()->location_program);
        $this->assertSame($this->lama->id, $mahasiswa->fresh()->location_program);
    }

    public function test_edit_user_role_mahasiswa_updates_mahasiswa_location_only(): void
    {
        $this->loginAs('admin');
        $user = $this->account('mahasiswa', 'mhs@pps.test', $this->lama->id);
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->lama->id]);
        $dpl = Dpl::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->lama->id]);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Ani', 'email' => 'mhs@pps.test', 'location_program' => $this->baru->id])
            ->assertJson(['success' => true]);

        $this->assertSame($this->baru->id, $user->fresh()->location_program);
        $this->assertSame($this->baru->id, $mahasiswa->fresh()->location_program);
        $this->assertSame($this->lama->id, $dpl->fresh()->location_program);
    }

    public function test_edit_user_null_location_is_synced_to_person_record(): void
    {
        $this->loginAs('admin');
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Dr. Dpl', 'email' => 'dpl@pps.test', 'location_program' => null])
            ->assertJson(['success' => true]);

        $this->assertNull($user->fresh()->location_program);
        $this->assertNull($dpl->fresh()->location_program);
    }

    public function test_edit_user_location_sync_follows_email_change(): void
    {
        $this->loginAs('admin');
        $user = $this->account('mahasiswa', 'lama@pps.test', $this->lama->id);
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'lama@pps.test', 'location_program' => $this->lama->id]);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Ani', 'email' => 'baru@pps.test', 'location_program' => $this->baru->id])
            ->assertJson(['success' => true]);

        $fresh = $mahasiswa->fresh();
        $this->assertSame('baru@pps.test', $fresh->email);
        $this->assertSame($this->baru->id, $fresh->location_program);
    }

    public function test_edit_user_rejects_invalid_location_and_keeps_data(): void
    {
        $this->loginAs('admin');
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Dr. Dpl', 'email' => 'dpl@pps.test', 'location_program' => (string) Str::uuid()])
            ->assertJsonValidationErrors('location_program', 'errors');

        $this->assertSame($this->lama->id, $user->fresh()->location_program);
        $this->assertSame($this->lama->id, $dpl->fresh()->location_program);
    }

    // AC5 (User)
    public function test_edit_user_flushes_cache_only_when_location_changes(): void
    {
        $this->loginAs('admin');
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);
        Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);
        Cache::forever(PetaSebaranService::VERSION_CACHE_KEY, 5);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Nama Lain', 'email' => 'dpl@pps.test', 'location_program' => $this->lama->id])
            ->assertJson(['success' => true]);
        $this->assertSame(5, $this->cacheVersion());

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Nama Lain', 'email' => 'dpl@pps.test', 'location_program' => $this->baru->id])
            ->assertJson(['success' => true]);
        $this->assertSame(6, $this->cacheVersion());
    }

    public function test_edit_user_flushes_cache_when_only_person_record_was_out_of_sync(): void
    {
        $this->loginAs('admin');
        $user = $this->account('mahasiswa', 'mhs@pps.test', $this->baru->id);
        Mahasiswa::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->lama->id]);
        Cache::forever(PetaSebaranService::VERSION_CACHE_KEY, 5);

        $this->put('user/updateuser', ['id' => $user->id, 'name' => 'Ani', 'email' => 'mhs@pps.test', 'location_program' => $this->baru->id])
            ->assertJson(['success' => true]);

        $this->assertSame(6, $this->cacheVersion());
    }

    // AC4
    public function test_dpl_profile_without_dpl_record_inherits_account_location(): void
    {
        $user = $this->loginAs('dpl', ['location_program' => $this->lama->id]);
        $this->assertDatabaseMissing('dpl', ['email' => $user->email]);

        $this->put('profile/update', ['nama' => 'Dr. Budi', 'nidn' => '0412345678', 'prodi' => 'TI', 'phone' => '0812-3456'])
            ->assertJson(['success' => true]);

        $this->assertSame($user->fresh()->location_program, Dpl::where('email', $user->email)->value('location_program'));
    }

    public function test_dpl_profile_does_not_overwrite_existing_dpl_location(): void
    {
        $user = $this->loginAs('dpl', ['location_program' => $this->lama->id]);
        $dpl = Dpl::factory()->create(['email' => $user->email, 'location_program' => $this->baru->id]);

        $this->put('profile/update', ['nama' => 'Dr. Budi', 'nidn' => '0412345678', 'prodi' => 'TI', 'phone' => '0812-3456'])
            ->assertJson(['success' => true]);

        $fresh = $dpl->fresh();
        $this->assertSame('Dr. Budi', $fresh->nama);
        $this->assertSame($this->baru->id, $fresh->location_program);
        $this->assertSame(1, Dpl::where('email', $user->email)->count());
    }

    public function test_dpl_profile_ignores_location_program_from_input(): void
    {
        $user = $this->loginAs('dpl', ['location_program' => $this->lama->id]);

        $this->put('profile/update', ['nama' => 'Dr. Budi', 'nidn' => '0412345678', 'prodi' => 'TI', 'phone' => '0812-3456', 'location_program' => $this->baru->id])
            ->assertJson(['success' => true]);

        $this->assertSame($this->lama->id, Dpl::where('email', $user->email)->value('location_program'));
        $this->assertSame($this->lama->id, $user->fresh()->location_program);
    }

    // AC6
    public function test_sync_command_defaults_to_dry_run_without_writes(): void
    {
        $user = $this->account('mahasiswa', 'mhs@pps.test', $this->lama->id);
        Mahasiswa::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->baru->id]);

        $this->artisan('users:sync-location')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame($this->lama->id, $user->fresh()->location_program);
    }

    public function test_sync_command_dry_run_wins_over_force(): void
    {
        $user = $this->account('dpl', 'dpl@pps.test', $this->lama->id);
        Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->baru->id]);

        $this->artisan('users:sync-location', ['--force' => true, '--dry-run' => true])->assertSuccessful();

        $this->assertSame($this->lama->id, $user->fresh()->location_program);
    }

    public function test_sync_command_force_fixes_out_of_sync_users(): void
    {
        $mhs = $this->account('mahasiswa', 'mhs@pps.test', $this->lama->id);
        Mahasiswa::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->baru->id]);
        $dpl = $this->account('dpl', 'dpl@pps.test', null);
        Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->baru->id]);
        $synced = $this->account('mahasiswa', 'ok@pps.test', $this->lama->id);
        Mahasiswa::factory()->create(['email' => 'ok@pps.test', 'location_program' => $this->lama->id]);
        $admin = $this->account('admin', 'admin@pps.test', $this->lama->id);

        $this->artisan('users:sync-location', ['--force' => true, '--chunk' => 1])
            ->expectsOutputToContain('Selesai.')
            ->assertSuccessful();

        $this->assertSame($this->baru->id, $mhs->fresh()->location_program);
        $this->assertSame($this->baru->id, $dpl->fresh()->location_program);
        $this->assertSame($this->lama->id, $synced->fresh()->location_program);
        $this->assertSame($this->lama->id, $admin->fresh()->location_program);

        $this->login('mhs@pps.test', 'Kota Cimahi')->assertJson(['success' => true]);
    }

    public function test_sync_command_matches_email_case_insensitively(): void
    {
        $user = $this->account('mahasiswa', 'mhs@pps.test', $this->lama->id);
        Mahasiswa::factory()->create(['email' => 'MHS@pps.test', 'location_program' => $this->baru->id]);

        $this->artisan('users:sync-location', ['--force' => true])->assertSuccessful();

        $this->assertSame($this->baru->id, $user->fresh()->location_program);
    }

    public function test_sync_command_skips_email_present_in_both_tables(): void
    {
        $user = $this->account('mahasiswa', 'both@pps.test', $this->lama->id);
        Mahasiswa::factory()->create(['email' => 'both@pps.test', 'location_program' => $this->baru->id]);
        Dpl::factory()->create(['email' => 'both@pps.test', 'location_program' => $this->baru->id]);

        $this->artisan('users:sync-location', ['--force' => true])
            ->expectsOutputToContain('Ambigu')
            ->assertSuccessful();

        $this->assertSame($this->lama->id, $user->fresh()->location_program);
    }

    public function test_sync_command_skips_multiple_sources_with_different_location(): void
    {
        $user = $this->account('dpl', 'dup@pps.test', $this->lama->id);
        Dpl::factory()->create(['email' => 'dup@pps.test', 'location_program' => $this->baru->id]);
        Dpl::factory()->create(['email' => 'dup@pps.test', 'location_program' => LokasiProgram::factory()->create()->id]);

        $this->artisan('users:sync-location', ['--force' => true])->assertSuccessful();

        $this->assertSame($this->lama->id, $user->fresh()->location_program);
    }

    public function test_sync_command_updates_multiple_sources_with_same_location(): void
    {
        $user = $this->account('dpl', 'dup@pps.test', $this->lama->id);
        Dpl::factory()->count(2)->create(['email' => 'dup@pps.test', 'location_program' => $this->baru->id]);

        $this->artisan('users:sync-location', ['--force' => true])->assertSuccessful();

        $this->assertSame($this->baru->id, $user->fresh()->location_program);
    }

    public function test_sync_command_skips_null_source_and_missing_source(): void
    {
        $nullSource = $this->account('mahasiswa', 'null@pps.test', $this->lama->id);
        Mahasiswa::factory()->create(['email' => 'null@pps.test', 'location_program' => null]);
        $noSource = $this->account('dpl', 'none@pps.test', $this->lama->id);

        $this->artisan('users:sync-location', ['--force' => true])->assertSuccessful();

        $this->assertSame($this->lama->id, $nullSource->fresh()->location_program);
        $this->assertSame($this->lama->id, $noSource->fresh()->location_program);
    }

    // AC7
    public function test_non_admin_cannot_call_update_endpoints(): void
    {
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs@pps.test', 'location_program' => $this->lama->id]);
        $mhsUser = $this->account('mahasiswa', 'mhs@pps.test', $this->lama->id);
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);
        $dplUser = $this->account('dpl', 'dpl@pps.test', $this->lama->id);

        // Role non-admin diarahkan ke home; pemantau (kepala/pemda) ditolak 403 oleh KepalaReadOnly
        foreach (['pt' => 302, 'dpl' => 302, 'mahasiswa' => 302, 'kepala' => 403, 'pemda' => 403] as $role => $status) {
            $this->loginAs($role);
            Cache::forever(PetaSebaranService::VERSION_CACHE_KEY, 5);

            $this->put('mahasiswa/update', $this->mahasiswaPayload($mahasiswa))->assertStatus($status);
            $this->put('dpl/update', $this->dplPayload($dpl))->assertStatus($status);
            $this->put('user/updateuser', ['id' => $dplUser->id, 'name' => 'x', 'email' => 'dpl@pps.test', 'location_program' => $this->baru->id])
                ->assertStatus($status);
            $this->assertSame(5, $this->cacheVersion(), $role);
        }

        foreach ([$mahasiswa, $mhsUser, $dpl, $dplUser] as $model) {
            $this->assertSame($this->lama->id, $model->fresh()->location_program);
        }
    }

    public function test_guest_cannot_call_update_endpoints(): void
    {
        $dpl = Dpl::factory()->create(['email' => 'dpl@pps.test', 'location_program' => $this->lama->id]);
        $dplUser = $this->account('dpl', 'dpl@pps.test', $this->lama->id);

        $this->put('dpl/update', $this->dplPayload($dpl))->assertRedirect(route('login'));
        $this->put('user/updateuser', ['id' => $dplUser->id, 'name' => 'x', 'email' => 'dpl@pps.test', 'location_program' => $this->baru->id])
            ->assertRedirect(route('login'));

        $this->assertSame($this->lama->id, $dplUser->fresh()->location_program);
        $this->assertSame($this->lama->id, DB::table('dpl')->where('id_dpl', $dpl->id_dpl)->value('location_program'));
    }
}
