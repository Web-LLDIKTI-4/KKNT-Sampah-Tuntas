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

    public function test_listdataserver_default_order_is_nama_kecamatan(): void
    {
        $this->loginAs('admin');
        foreach (['Cicendo', 'Andir', 'Bojongloa'] as $nama) {
            Kecamatan::factory()->create(['kecamatan' => $nama]);
        }

        // DataTables default sends order on column 0 (No, orderable=false)
        $query = 'kecamatan/listdataserver?draw=1&start=0&length=10&order[0][column]=0&order[0][dir]=desc'
            .'&columns[0][data]=DT_RowIndex&columns[0][name]=DT_RowIndex&columns[0][orderable]=false&columns[0][searchable]=false'
            .'&columns[1][data]=kecamatan&columns[1][name]=kecamatan&columns[1][orderable]=true&columns[1][searchable]=true';
        $nama = collect($this->getJson($query, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->pluck('kecamatan')->all();

        $this->assertSame(['Andir', 'Bojongloa', 'Cicendo'], $nama);
    }

    public function test_listdataserver_malformed_order_does_not_500(): void
    {
        $this->loginAs('admin');
        Kecamatan::factory()->count(2)->create();

        // >50 columns: extras are dropped, order pointing at a dropped column is ignored
        $many = implode('&', array_map(fn ($i) => "columns[$i][data]=x$i", range(0, 59))).'&order[0][column]=55';

        foreach ([
            'order=x',
            'order[0][dir][]=x',
            'order[0][column]=1&columns[1]=x',
            'columns=x',
            'columns[0][search]=x',
            'columns[0][data][]=x&columns[0][searchable]=true',
            'columns[0][data]=nama&columns[0][searchable]=true&columns[0][search][value][]=x',
            'columns[x][data]=nama&order[0][column]=x',
            'columns[0][data]=a;b&columns[0][name]=a;b&columns[0][orderable]=true&order[0][column]=0&order[0][dir]=asc',
            'search=x',
            'search[value][]=x',
            'columns[-1][data]=kecamatan&columns[-1][orderable]=true&order[0][column]=-1&order[0][dir]=asc',
            'columns[99999][data]=kecamatan&columns[99999][orderable]=true&order[0][column]=99999',
            'columns[99999999999999999999][data]=kecamatan&order[0][column]=99999999999999999999',
            'columns[7][data]=kecamatan&columns[7][orderable]=true&columns[3][data]=kecamatan&order[0][column]=7&order[0][dir]=asc',
            'columns[0][search][regex]=true&search[regex]=true',
            'columns[0][data]=kecamatan%0A&columns[0][name]=kecamatan%0A&columns[0][orderable]=true&order[0][column]=0',
            'searchPanes[kecamatan][]=x',
            $many,
        ] as $order) {
            $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10&'.$order, ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->assertJsonCount(2, 'data');
        }

        // 2500-term search is capped to 100 chars / 10 terms
        $start = microtime(true);
        $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10&columns[0][data]=kecamatan&columns[0][searchable]=true&columns[0][search][value]='.str_repeat('a+', 2500).'&search[value]='.str_repeat('a+', 2500), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
        $this->assertLessThan(2, microtime(true) - $start);

        // regex=true is forced off: '[' becomes a LIKE match instead of a REGEXP error
        $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10&columns[0][data]=kecamatan&columns[0][searchable]=true&columns[0][search][value]=%5B&columns[0][search][regex]=true', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonCount(0, 'data');
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
