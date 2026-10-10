<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\CapaianKegiatan;
use App\Models\Pjdesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PjdesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_ketua_kelompok(): void
    {
        $this->loginAs('admin');
        $ketua = User::factory()->create(['akses' => 'pjdesa']);
        $desa = Desa::factory()->create();

        $this->put('pjdesa/insert', ['id_desa' => $desa->id_desa, 'email' => $ketua->email])
            ->assertJson(['success' => true]);
        $this->put('pjdesa/insert', ['id_desa' => $desa->id_desa, 'email' => $ketua->email])
            ->assertJsonPath('errors.email.0', 'Data sudah ada!');
    }

    public function test_only_users_with_pjdesa_access_can_be_assigned(): void
    {
        $this->loginAs('admin');
        $biasa = User::factory()->create(['akses' => null]);

        $this->put('pjdesa/insert', ['id_desa' => Desa::factory()->create()->id_desa, 'email' => $biasa->email])
            ->assertJsonPath('errors.email.0', 'Ketua Kelompok tidak valid.');
    }

    public function test_pjdesa_with_capaian_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $pj = Pjdesa::create(['id_desa' => Desa::factory()->create()->id_desa, 'email' => 'ketua@pps.test']);
        CapaianKegiatan::factory()->create(['email' => 'ketua@pps.test', 'id_pjdesa' => $pj->id_pjdesa]);

        $this->put('pjdesa/destroy', ['id_pjdesa' => $pj->id_pjdesa])->assertJson(['success' => false]);
        $this->assertDatabaseHas('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
    }

    public function test_tambah_form_renders(): void
    {
        $this->loginAs('admin');
        User::factory()->create(['akses' => 'pjdesa']);

        $this->get('pjdesa/tambah')->assertOk()->assertSee('data-ajax-form', false);
    }
}
