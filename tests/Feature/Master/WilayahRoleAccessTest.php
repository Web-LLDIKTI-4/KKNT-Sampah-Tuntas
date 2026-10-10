<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\PenguranganSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WilayahRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['kecamatan', 'kecamatan/listdata', 'kecamatan/tambah', 'desa', 'desa/listdata', 'desa/tambah'];

    public function test_admin_and_pemda_can_open_wilayah_pages(): void
    {
        foreach (['admin', 'pemda'] as $role) {
            $this->loginAs($role);
            foreach (self::PAGES as $uri) {
                $this->get($uri)->assertOk();
            }
        }
    }

    public function test_other_roles_are_redirected_from_wilayah_pages(): void
    {
        foreach (['kepala', 'pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            foreach (self::PAGES as $uri) {
                $this->get($uri)->assertRedirect(route('home'));
            }
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('kecamatan')->assertRedirect(route('login'));
        $this->put('desa/insert', [])->assertRedirect(route('login'));
    }

    public function test_pemda_can_crud_kecamatan_and_desa(): void
    {
        $this->loginAs('pemda');

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])->assertJson(['success' => true]);
        $kecamatan = Kecamatan::where('kecamatan', 'Coblong')->firstOrFail();
        $this->put('kecamatan/update', ['id_kecamatan' => $kecamatan->id_kecamatan, 'kecamatan' => 'Coblong Baru'])
            ->assertJson(['success' => true]);
        $this->get('kecamatan/edit/'.$kecamatan->id_kecamatan)->assertOk();

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Dago')->firstOrFail();
        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago Atas'])
            ->assertJson(['success' => true]);
        $this->get('desa/edit/'.$desa->id_desa)->assertOk();

        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertJson(['success' => true]);
        $this->put('kecamatan/destroy', ['id_kecamatan' => $kecamatan->id_kecamatan])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('desa', ['id_desa' => $desa->id_desa]);
        $this->assertDatabaseMissing('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
    }

    public function test_kepala_write_is_forbidden(): void
    {
        $this->loginAs('kepala');
        $desa = Desa::factory()->create();

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])->assertForbidden();
        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $desa->id_kecamatan, 'desa' => 'X'])
            ->assertForbidden();
        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertForbidden();
        $this->put('kecamatan/destroy', ['id_kecamatan' => $desa->id_kecamatan])->assertForbidden();

        $this->assertDatabaseMissing('kecamatan', ['kecamatan' => 'Coblong']);
        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa, 'desa' => $desa->desa]);
    }

    public function test_non_admin_non_pemda_roles_cannot_write(): void
    {
        $desa = Desa::factory()->create();

        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])->assertRedirect(route('home'));
            $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $desa->id_kecamatan, 'desa' => 'X'])
                ->assertRedirect(route('home'));
            $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertRedirect(route('home'));
        }

        $this->assertDatabaseMissing('kecamatan', ['kecamatan' => 'Coblong']);
        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa, 'desa' => $desa->desa]);
    }

    public function test_pemda_cannot_write_other_master_data(): void
    {
        $this->loginAs('pemda');

        $this->put('pjdesa/insert', [])->assertForbidden();
        $this->put('lokasiprogram/insert', [])->assertForbidden();
    }

    public function test_desa_with_pengurangan_sampah_cannot_be_deleted(): void
    {
        $this->loginAs('pemda');
        $desa = Desa::factory()->create();
        PenguranganSampah::create([
            'id_sampah' => (string) Str::uuid(),
            'email' => 'ketua@example.test',
            'id_desa' => $desa->id_desa,
            'bulan' => now()->startOfMonth()->toDateString(),
            'jml_rw' => 1, 'jml_penduduk' => 1, 'jml_rumah' => 1, 'jml_rumah_memilah' => 0,
            'timbulan' => 0, 'organik_sumber' => 0, 'organik_dlh' => 0,
            'anorganik_sumber' => 0, 'pengurangan' => 0, 'belum_terkelola' => 0,
        ]);

        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertJson(['success' => false]);
        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa]);
    }

    public function test_destroy_with_invalid_id_returns_json_not_found(): void
    {
        $this->loginAs('pemda');
        $desa = Desa::factory()->create();

        foreach ([[$desa->id_desa], 'bukan-uuid', null] as $id) {
            $this->put('desa/destroy', ['id_desa' => $id])->assertNotFound()->assertJson(['success' => false]);
            $this->put('kecamatan/destroy', ['id_kecamatan' => $id])->assertNotFound()->assertJson(['success' => false]);
        }
        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa]);
    }

    public function test_desa_datatable_searches_and_orders_by_kecamatan(): void
    {
        $this->loginAs('pemda');
        $a = Desa::factory()->create(['id_kecamatan' => Kecamatan::create(['kecamatan' => 'Andir'])->id_kecamatan, 'desa' => 'Satu']);
        Desa::factory()->create(['id_kecamatan' => Kecamatan::create(['kecamatan' => 'Bojong'])->id_kecamatan, 'desa' => 'Dua']);

        $params = [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [
                ['data' => 'id_desa', 'name' => 'id_desa', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
                ['data' => 'kecamatan', 'name' => 'kecamatan', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
                ['data' => 'desa', 'name' => 'desa', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ],
        ];

        $res = $this->getJson('desa/listdataserver?'.http_build_query($params + ['search' => ['value' => 'Andir']]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame(1, $res->json('recordsFiltered'));
        $this->assertSame('Andir', $res->json('data.0.kecamatan'));
        $this->assertSame($a->id_desa, $res->json('data.0.id_desa'));

        $res = $this->getJson('desa/listdataserver?'.http_build_query($params + ['order' => [['column' => 1, 'dir' => 'desc']], 'search' => ['value' => '']]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame(['Bojong', 'Andir'], array_column($res->json('data'), 'kecamatan'));
    }

    public function test_desa_datatable_ignores_ambiguous_columns(): void
    {
        $this->loginAs('pemda');
        Desa::factory()->create(['id_kecamatan' => Kecamatan::create(['kecamatan' => 'Andir'])->id_kecamatan, 'desa' => 'Satu']);

        $params = [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [
                ['data' => 'id_kecamatan', 'name' => 'id_kecamatan', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => 'x']],
                ['data' => 'created_at', 'name' => 'created_at', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
                ['data' => 'desa', 'name' => 'desa', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ],
            'order' => [['column' => 0, 'dir' => 'asc'], ['column' => 1, 'dir' => 'desc']],
            'search' => ['value' => 'Satu'],
        ];

        $res = $this->getJson('desa/listdataserver?'.http_build_query($params), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame(1, $res->json('recordsFiltered'));
    }

    public function test_menu_shows_kelola_kegiatan_only_for_pemda(): void
    {
        $this->loginAs('pemda');
        $this->get('home')->assertOk()->assertSee('Kelola Kegiatan')->assertSee(url('kecamatan'), false);

        $this->loginAs('kepala');
        $this->get('home')->assertOk()->assertDontSee('Kelola Kegiatan');
    }
}
