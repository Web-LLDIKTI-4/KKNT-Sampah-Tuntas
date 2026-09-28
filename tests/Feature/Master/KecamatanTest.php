<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KecamatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_kecamatan(): void
    {
        $this->loginAs('admin');

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('kecamatan', ['kecamatan' => 'Coblong']);
    }

    public function test_duplicate_and_empty_name_are_rejected(): void
    {
        $this->loginAs('admin');
        Kecamatan::factory()->create(['kecamatan' => 'Coblong']);

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])
            ->assertOk()->assertJson(['success' => false])->assertJsonPath('errors.kecamatan.0', 'Data sudah ada!');

        $this->put('kecamatan/insert', ['kecamatan' => ''])
            ->assertJsonPath('errors.kecamatan.0', 'Nama kecamatan harus diisi.');
    }

    public function test_admin_can_update_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('kecamatan/update', ['id_kecamatan' => $kecamatan->id_kecamatan, 'kecamatan' => 'Sukajadi'])
            ->assertJson(['success' => true]);

        $this->assertSame('Sukajadi', $kecamatan->fresh()->kecamatan);
    }

    public function test_update_requires_valid_id(): void
    {
        $this->loginAs('admin');

        $this->put('kecamatan/update', ['id_kecamatan' => 'bukan-uuid', 'kecamatan' => 'X'])
            ->assertJson(['success' => false])->assertJsonValidationErrors('id_kecamatan', 'errors');
    }

    public function test_delete_is_rejected_when_kecamatan_has_desa(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();

        $this->put('kecamatan/destroy', ['id_kecamatan' => $desa->id_kecamatan])
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $desa->id_kecamatan]);
    }

    public function test_admin_can_delete_unused_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('kecamatan/destroy', ['id_kecamatan' => $kecamatan->id_kecamatan])
            ->assertJson(['success' => true, 'message' => 'Data berhasil dihapus']);

        $this->assertDatabaseMissing('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
    }

    public function test_listdataserver_returns_datatables_json(): void
    {
        $this->loginAs('admin');
        Kecamatan::factory()->count(3)->create();

        $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 3);
    }

    public function test_edit_missing_record_returns_404(): void
    {
        $this->loginAs('admin');

        $this->get('kecamatan/edit/'.fake()->uuid())->assertNotFound();
    }

    public function test_non_admin_cannot_modify_kecamatan(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])->assertRedirect(route('home'));
        $this->assertDatabaseMissing('kecamatan', ['kecamatan' => 'Coblong']);
    }
}
