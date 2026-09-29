<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bulk_creates_accounts_with_correct_roles(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        $dpl = Dpl::factory()->create();

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
            'password' => 'Rahasia123', 'role' => 'admin',
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
}
