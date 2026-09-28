<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_desa(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Dago')->firstOrFail();

        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago Atas'])
            ->assertJson(['success' => true]);
        $this->assertSame('Dago Atas', $desa->fresh()->desa);

        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('desa', ['id_desa' => $desa->id_desa]);
    }

    public function test_same_name_is_allowed_in_other_kecamatan_only(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create(['desa' => 'Sukamaju']);
        $lain = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $desa->id_kecamatan, 'desa' => 'Sukamaju'])
            ->assertJsonPath('errors.desa.0', 'Data sudah ada!');
        $this->put('desa/insert', ['id_kecamatan' => $lain->id_kecamatan, 'desa' => 'Sukamaju'])
            ->assertJson(['success' => true]);
    }

    public function test_desa_with_profile_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $profile = Desaprofile::factory()->create();

        $this->put('desa/destroy', ['id_desa' => $profile->id_desa])->assertJson(['success' => false]);
        $this->assertDatabaseHas('desa', ['id_desa' => $profile->id_desa]);
    }

    public function test_listdataserver_includes_kecamatan_name(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();

        $this->getJson('desa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonFragment(['kecamatan' => $desa->kecamatan->kecamatan]);
    }
}
