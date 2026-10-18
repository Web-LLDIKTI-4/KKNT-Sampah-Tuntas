<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\PetaSebaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MahasiswaEditTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Mahasiswa $mahasiswa, array $overrides = []): array
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
            'location_program' => LokasiProgram::factory()->create()->id,
        ];
    }

    public function test_admin_can_open_edit_form(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create();
        $sp = Satuanpendidikan::factory()->create();

        $this->get('mahasiswa/edit/'.$mahasiswa->id_mahasiswa)
            ->assertOk()
            ->assertViewIs('mahasiswa.edit')
            ->assertViewHas('data', fn ($data) => $data->is($mahasiswa))
            ->assertViewHas('perguruanTinggi', fn ($pt) => $pt[$sp->npsn] === $sp->nm_lemb)
            ->assertViewHas('lokasiProgram')
            ->assertViewHas('canEditEmail', true)
            ->assertSee('name="nim"', false)
            ->assertSee('name="tahun_masuk"', false);
    }

    public function test_edit_form_locks_email_when_mahasiswa_has_account(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs.akun@pps.test']);
        User::factory()->role('mahasiswa')->create(['email' => 'mhs.akun@pps.test']);

        $this->get('mahasiswa/edit/'.$mahasiswa->id_mahasiswa)->assertOk()->assertViewHas('canEditEmail', false);
    }

    public function test_edit_form_locks_email_when_mahasiswa_is_ketua_or_mentored(): void
    {
        $this->loginAs('admin');
        $ketua = Mahasiswa::factory()->create(['email' => 'ketua@pps.test']);
        $bimbingan = Mahasiswa::factory()->create(['email' => 'bimbingan@pps.test']);
        DB::table('pj_desa')->insert(['id_pjdesa' => (string) Str::uuid(), 'email' => 'ketua@pps.test']);
        DB::table('dpl_mentoring')->insert(['id_mentoring' => (string) Str::uuid(), 'email_mahasiswa' => 'bimbingan@pps.test', 'email_dpl' => 'dpl@pps.test']);

        $this->get('mahasiswa/edit/'.$ketua->id_mahasiswa)->assertOk()->assertViewHas('canEditEmail', false);
        $this->get('mahasiswa/edit/'.$bimbingan->id_mahasiswa)->assertOk()->assertViewHas('canEditEmail', false);
    }

    public function test_edit_unknown_mahasiswa_returns_404(): void
    {
        $this->loginAs('admin');

        $this->get('mahasiswa/edit/'.Str::uuid())->assertNotFound();
        $this->get('mahasiswa/edit/not-a-uuid')->assertNotFound();
    }

    public function test_listing_shows_edit_button_for_mahasiswa(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create();

        $html = $this->getJson('mahasiswa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0.action');

        $this->assertStringContainsString(url('mahasiswa/edit/'.$mahasiswa->id_mahasiswa), $html);
    }

    public function test_admin_can_update_mahasiswa_and_email_is_unchanged_when_has_account(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs.lama@pps.test']);
        User::factory()->role('mahasiswa')->create(['email' => 'mhs.lama@pps.test']);
        $payload = $this->payload($mahasiswa, ['email' => 'mhs.baru@pps.test']);

        $this->put('mahasiswa/update', $payload)->assertJson(['success' => true]);

        $fresh = $mahasiswa->fresh();
        $this->assertSame('mhs.lama@pps.test', $fresh->email);
        $this->assertSame($payload['tahun_masuk'], (int) $fresh->tahun_masuk);
        foreach (['nim', 'nama', 'phone', 'prodi', 'kodept', 'location_program'] as $field) {
            $this->assertSame($payload[$field], $fresh->{$field});
        }
    }

    public function test_update_flushes_peta_sebaran_cache(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create();
        Cache::forever(PetaSebaranService::VERSION_CACHE_KEY, 5);

        $this->put('mahasiswa/update', $this->payload($mahasiswa))->assertJson(['success' => true]);

        $this->assertSame(6, (int) Cache::get(PetaSebaranService::VERSION_CACHE_KEY));
    }

    public function test_email_can_be_updated_when_not_locked(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs.lama@pps.test']);

        $this->put('mahasiswa/update', $this->payload($mahasiswa, ['email' => ' mhs.baru@pps.test ']))->assertJson(['success' => true]);

        $this->assertSame('mhs.baru@pps.test', $mahasiswa->fresh()->email);
    }

    public function test_email_is_unchanged_when_mahasiswa_is_ketua(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'ketua@pps.test']);
        DB::table('pj_desa')->insert(['id_pjdesa' => (string) Str::uuid(), 'email' => 'ketua@pps.test']);

        $this->put('mahasiswa/update', $this->payload($mahasiswa, ['email' => 'baru@pps.test']))->assertJson(['success' => true]);

        $this->assertSame('ketua@pps.test', $mahasiswa->fresh()->email);
    }

    public function test_update_rejects_email_used_by_another_mahasiswa_user_or_dpl(): void
    {
        $this->loginAs('admin');
        Mahasiswa::factory()->create(['email' => 'mhs.lain@pps.test']);
        User::factory()->role('pt')->create(['email' => 'user@pps.test']);
        Dpl::factory()->create(['email' => 'dpl@pps.test']);
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs.lama@pps.test']);

        foreach (['mhs.lain@pps.test', 'user@pps.test', 'dpl@pps.test'] as $email) {
            $this->put('mahasiswa/update', $this->payload($mahasiswa, ['email' => $email]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors(['email']);
        }

        $this->assertSame('mhs.lama@pps.test', $mahasiswa->fresh()->email);
    }

    public function test_unchanged_legacy_duplicate_email_can_be_saved_but_not_reused(): void
    {
        $this->loginAs('admin');
        Mahasiswa::factory()->create(['email' => 'dobel@pps.test']);
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'dobel@pps.test']);
        $other = Mahasiswa::factory()->create(['email' => 'lain@pps.test']);

        // Email unchanged (case/space normalized) still saves despite legacy duplicate
        $this->put('mahasiswa/update', $this->payload($mahasiswa, ['email' => ' Dobel@pps.test ']))->assertJson(['success' => true]);
        $this->assertSame('Mahasiswa Baru', $mahasiswa->fresh()->nama);

        // Changing to an email already in use is still rejected
        $this->put('mahasiswa/update', $this->payload($other, ['email' => 'dobel@pps.test']))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['email']);
        $this->assertSame('lain@pps.test', $other->fresh()->email);
    }

    public function test_update_syncs_linked_user_name_only_for_mahasiswa_role(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs.akun@pps.test']);
        $user = User::factory()->role('mahasiswa')->create(['email' => 'mhs.akun@pps.test', 'name' => 'Nama Lama']);

        $this->put('mahasiswa/update', $this->payload($mahasiswa))->assertJson(['success' => true]);

        $this->assertSame('Mahasiswa Baru', $user->fresh()->name);
    }

    public function test_update_does_not_rename_non_mahasiswa_user_with_same_email(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create(['email' => 'mhs.akun@pps.test']);
        $user = User::factory()->role('dpl')->create(['email' => 'mhs.akun@pps.test', 'name' => 'Nama Lama']);

        $this->put('mahasiswa/update', $this->payload($mahasiswa))->assertJson(['success' => true]);

        $this->assertSame('Nama Lama', $user->fresh()->name);
    }

    public function test_update_with_array_id_returns_validation_error(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create();

        $this->put('mahasiswa/update', $this->payload($mahasiswa, ['id_mahasiswa' => [$mahasiswa->id_mahasiswa]]))
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['id_mahasiswa']);
    }

    public function test_update_rejects_invalid_tahun_masuk_kodept_and_location_program(): void
    {
        $this->loginAs('admin');
        $mahasiswa = Mahasiswa::factory()->create();
        $nama = $mahasiswa->nama;

        $this->put('mahasiswa/update', $this->payload($mahasiswa, [
            'tahun_masuk' => 1980,
            'kodept' => '999999',
            'location_program' => (string) Str::uuid(),
        ]))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['tahun_masuk', 'kodept', 'location_program']);

        $this->assertSame($nama, $mahasiswa->fresh()->nama);
    }

    public function test_update_rejects_nim_already_used_in_same_pt(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        Mahasiswa::factory()->create(['nim' => '2200000001', 'kodept' => $sp->npsn]);
        $mahasiswa = Mahasiswa::factory()->create(['kodept' => $sp->npsn]);

        $this->put('mahasiswa/update', $this->payload($mahasiswa, ['nim' => '2200000001', 'kodept' => $sp->npsn]))
            ->assertJsonValidationErrors(['nim']);

        // NIM milik sendiri tetap boleh
        $this->put('mahasiswa/update', $this->payload($mahasiswa, ['nim' => $mahasiswa->nim, 'kodept' => $sp->npsn]))
            ->assertJson(['success' => true]);
    }

    public function test_non_admin_cannot_edit_or_update_mahasiswa(): void
    {
        $mahasiswa = Mahasiswa::factory()->create();

        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);

            $this->get('mahasiswa/edit/'.$mahasiswa->id_mahasiswa)->assertRedirect(route('home'));
            $this->put('mahasiswa/update', $this->payload($mahasiswa))->assertRedirect(route('home'));
        }

        $this->assertNotSame('Mahasiswa Baru', $mahasiswa->fresh()->nama);
    }
}
