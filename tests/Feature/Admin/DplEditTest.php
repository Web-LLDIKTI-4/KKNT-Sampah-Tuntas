<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DplEditTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Dpl $dpl, array $overrides = []): array
    {
        return $overrides + [
            'id_dpl' => $dpl->id_dpl,
            'email' => $dpl->email,
            'nidn' => '0411223344',
            'nama' => 'Dr. Baru, M.T.',
            'phone' => '081299990000',
            'prodi' => 'Teknik Sipil',
            'kodept' => Satuanpendidikan::factory()->create()->npsn,
            'location_program' => LokasiProgram::factory()->create()->id,
        ];
    }

    public function test_admin_can_open_edit_form(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();
        $sp = Satuanpendidikan::factory()->create();

        $this->get('dpl/edit/'.$dpl->id_dpl)
            ->assertOk()
            ->assertViewIs('dpl.edit')
            ->assertViewHas('data', fn ($data) => $data->is($dpl))
            ->assertViewHas('perguruanTinggi', fn ($pt) => $pt[$sp->npsn] === $sp->nm_lemb)
            ->assertViewHas('lokasiProgram')
            ->assertViewHas('canEditEmail', true);
    }

    public function test_edit_form_locks_email_when_dpl_has_account(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.akun@pps.test']);
        User::factory()->role('dpl')->create(['email' => 'dpl.akun@pps.test']);

        $this->get('dpl/edit/'.$dpl->id_dpl)->assertOk()->assertViewHas('canEditEmail', false);
    }

    public function test_edit_unknown_dpl_returns_404(): void
    {
        $this->loginAs('admin');

        $this->get('dpl/edit/'.Str::uuid())->assertNotFound();
        $this->get('dpl/edit/not-a-uuid')->assertNotFound();
    }

    public function test_listing_shows_edit_button_for_dpl(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();

        // Edit button is built client-side: edit prefix from the view + row key from the JSON
        $this->getJson('dpl/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.id_dpl', $dpl->id_dpl);
        $this->get('dpl/listdata')->assertOk()->assertSee('"edit":'.json_encode(url('dpl/edit').'/'), false);
    }

    public function test_admin_can_update_dpl_and_email_is_unchanged_when_dpl_has_account(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.lama@pps.test']);
        User::factory()->role('dpl')->create(['email' => 'dpl.lama@pps.test']);
        $payload = $this->payload($dpl, ['email' => 'dpl.baru@pps.test']);

        $this->put('dpl/update', $payload)->assertJson(['success' => true]);

        $fresh = $dpl->fresh();
        $this->assertSame('dpl.lama@pps.test', $fresh->email);
        foreach (['nidn', 'nama', 'phone', 'prodi', 'kodept', 'location_program'] as $field) {
            $this->assertSame($payload[$field], $fresh->{$field});
        }
    }

    public function test_email_can_be_updated_when_dpl_has_no_account(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.lama@pps.test']);

        $this->put('dpl/update', $this->payload($dpl, ['email' => ' dpl.baru@pps.test ']))->assertJson(['success' => true]);

        $this->assertSame('dpl.baru@pps.test', $dpl->fresh()->email);
    }

    public function test_update_rejects_email_used_by_another_dpl(): void
    {
        $this->loginAs('admin');
        Dpl::factory()->create(['email' => 'dpl.lain@pps.test']);
        $dpl = Dpl::factory()->create(['email' => 'dpl.lama@pps.test']);

        $this->put('dpl/update', $this->payload($dpl, ['email' => 'dpl.lain@pps.test']))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['email']);

        $this->assertSame('dpl.lama@pps.test', $dpl->fresh()->email);
    }

    public function test_update_rejects_email_used_by_any_user(): void
    {
        $this->loginAs('admin');
        User::factory()->role('mahasiswa')->create(['email' => 'terpakai@pps.test']);
        $dpl = Dpl::factory()->create(['email' => 'dpl.lama@pps.test']);

        $this->put('dpl/update', $this->payload($dpl, ['email' => 'terpakai@pps.test']))
            ->assertJsonValidationErrors(['email']);

        $this->assertSame('dpl.lama@pps.test', $dpl->fresh()->email);
    }

    public function test_update_rejects_email_longer_than_100_chars(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.lama@pps.test']);

        $this->put('dpl/update', $this->payload($dpl, ['email' => str_repeat('a', 92).'@pps.test']))
            ->assertJsonValidationErrors(['email']);

        $this->assertSame('dpl.lama@pps.test', $dpl->fresh()->email);
    }

    public function test_update_rejects_email_used_by_mahasiswa(): void
    {
        $this->loginAs('admin');
        Mahasiswa::factory()->create(['email' => 'mhs@pps.test']);
        $dpl = Dpl::factory()->create(['email' => 'dpl.lama@pps.test']);

        $this->put('dpl/update', $this->payload($dpl, ['email' => 'mhs@pps.test']))
            ->assertJsonValidationErrors(['email']);

        $this->assertSame('dpl.lama@pps.test', $dpl->fresh()->email);
    }

    public function test_update_with_array_id_dpl_returns_validation_error(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();

        // AjaxFormRequest reports validation errors as 200 + success:false
        $this->put('dpl/update', $this->payload($dpl, ['id_dpl' => [$dpl->id_dpl]]))
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['id_dpl']);
    }

    public function test_locked_dpl_posting_another_users_email_succeeds_with_email_unchanged(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.akun@pps.test']);
        User::factory()->role('dpl')->create(['email' => 'dpl.akun@pps.test']);
        User::factory()->role('mahasiswa')->create(['email' => 'lain@pps.test']);

        $this->put('dpl/update', $this->payload($dpl, ['email' => 'lain@pps.test']))->assertJson(['success' => true]);

        $this->assertSame('dpl.akun@pps.test', $dpl->fresh()->email);
    }

    public function test_update_syncs_linked_user_name(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.akun@pps.test']);
        $user = User::factory()->role('dpl')->create(['email' => 'dpl.akun@pps.test', 'name' => 'Nama Lama']);

        $this->put('dpl/update', $this->payload($dpl))->assertJson(['success' => true]);

        $this->assertSame('Dr. Baru, M.T.', $user->fresh()->name);
    }

    public function test_update_does_not_rename_non_dpl_user_with_same_email(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['email' => 'dpl.akun@pps.test']);
        $user = User::factory()->role('mahasiswa')->create(['email' => 'dpl.akun@pps.test', 'name' => 'Nama Lama']);

        $this->put('dpl/update', $this->payload($dpl))->assertJson(['success' => true]);

        $this->assertSame('Nama Lama', $user->fresh()->name);
    }

    public function test_location_program_can_be_cleared(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['location_program' => LokasiProgram::factory()->create()->id]);

        $this->put('dpl/update', $this->payload($dpl, ['location_program' => null]))->assertJson(['success' => true]);

        $this->assertNull($dpl->fresh()->location_program);
    }

    public function test_update_rejects_invalid_kodept_and_location_program(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();
        $nama = $dpl->nama;

        $this->put('dpl/update', $this->payload($dpl, ['kodept' => '999999', 'location_program' => (string) Str::uuid()]))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['kodept', 'location_program']);

        $this->assertSame($nama, $dpl->fresh()->nama);
    }

    public function test_update_rejects_nidn_already_used_in_same_pt(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        Dpl::factory()->create(['nidn' => '0400000001', 'kodept' => $sp->npsn]);
        $dpl = Dpl::factory()->create(['kodept' => $sp->npsn]);

        $this->put('dpl/update', $this->payload($dpl, ['nidn' => '0400000001', 'kodept' => $sp->npsn]))
            ->assertJsonValidationErrors(['nidn']);

        // NIDN milik sendiri tetap boleh
        $this->put('dpl/update', $this->payload($dpl, ['nidn' => $dpl->nidn, 'kodept' => $sp->npsn]))
            ->assertJson(['success' => true]);
    }

    public function test_non_admin_cannot_edit_or_update_dpl(): void
    {
        $dpl = Dpl::factory()->create();

        foreach (['pt', 'dpl'] as $role) {
            $this->loginAs($role);

            $this->get('dpl/edit/'.$dpl->id_dpl)->assertRedirect(route('home'));
            $this->put('dpl/update', $this->payload($dpl))->assertRedirect(route('home'));
        }

        $this->assertNotSame('Dr. Baru, M.T.', $dpl->fresh()->nama);
    }
}
