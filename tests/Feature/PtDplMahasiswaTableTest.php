<?php

namespace Tests\Feature;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Browser-like flow for PT: index page, listdata partial, then the DataTables ajax payload
class PtDplMahasiswaTableTest extends TestCase
{
    use RefreshDatabase;

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ptA = Satuanpendidikan::factory()->create();
        $this->ptB = Satuanpendidikan::factory()->create();

        Dpl::factory()->count(2)->create(['kodept' => $this->ptA->npsn]);
        Dpl::factory()->count(3)->create(['kodept' => $this->ptB->npsn]);
        Mahasiswa::factory()->count(2)->create(['kodept' => $this->ptA->npsn]);
        Mahasiswa::factory()->count(3)->create(['kodept' => $this->ptB->npsn]);
    }

    // Same query string DataTables builds from the view's column definitions
    private function dataTablesQuery(array $columns, string $search = ''): string
    {
        $cols = array_map(fn ($c) => [
            'data' => $c,
            'name' => $c,
            'searchable' => $c === 'DT_RowIndex' || $c === 'ketua_kelompok' || $c === 'action' ? 'false' : 'true',
            'orderable' => $c === 'DT_RowIndex' || $c === 'ketua_kelompok' || $c === 'action' ? 'false' : 'true',
            'search' => ['value' => '', 'regex' => 'false'],
        ], $columns);

        return http_build_query([
            'draw' => 1,
            'columns' => $cols,
            'order' => [['column' => 0, 'dir' => 'asc']],
            'start' => 0,
            'length' => 10,
            'search' => ['value' => $search, 'regex' => 'false'],
            '_' => 1,
        ]);
    }

    private function ajax(string $url)
    {
        return $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_pt_sees_only_own_dpl_through_browser_flow(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);

        $this->get('ptdpl')->assertOk();
        $this->get('ptdpl/listdata', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertSee(route('ptdpl.listdataserver'), false)
            ->assertDontSee(route('dpl.export'), false);

        $query = $this->dataTablesQuery(['DT_RowIndex', 'nidn', 'nama', 'email', 'phone', 'nm_lemb', 'location_program']);
        $json = $this->ajax('ptdpl/listdataserver?'.$query)->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 2)
            ->json('data');

        $this->assertCount(2, $json);
        $this->assertSame([$this->ptA->nm_lemb], array_values(array_unique(array_column($json, 'nm_lemb'))));
    }

    public function test_pt_sees_only_own_mahasiswa_through_browser_flow(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);

        $this->get('ptmahasiswa')->assertOk();
        $this->get('ptmahasiswa/listdata', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertSee(route('ptmahasiswa.listdataserver'), false)
            ->assertDontSee(route('mahasiswa.export'), false);

        $query = $this->dataTablesQuery(['DT_RowIndex', 'nim', 'nama', 'email', 'phone', 'nm_lemb', 'location_program', 'ketua_kelompok', 'nama_kecamatan', 'nama_desa', 'action']);
        $json = $this->ajax('ptmahasiswa/listdataserver?'.$query)->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 2)
            ->json('data');

        $this->assertCount(2, $json);
        $this->assertSame([$this->ptA->nm_lemb], array_values(array_unique(array_column($json, 'nm_lemb'))));
    }

    public function test_pt_search_and_order_stay_scoped_to_own_pt(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);
        $other = Dpl::where('kodept', $this->ptB->npsn)->first();

        $query = $this->dataTablesQuery(['DT_RowIndex', 'nidn', 'nama', 'email', 'phone', 'nm_lemb', 'location_program'], $other->email);
        $this->ajax('ptdpl/listdataserver?'.$query.'&order[0][column]=2&order[0][dir]=desc')->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 0);

        $other = Mahasiswa::where('kodept', $this->ptB->npsn)->first();
        $query = $this->dataTablesQuery(['DT_RowIndex', 'nim', 'nama', 'email', 'phone', 'nm_lemb', 'location_program', 'ketua_kelompok', 'nama_kecamatan', 'nama_desa', 'action'], $other->email);
        $this->ajax('ptmahasiswa/listdataserver?'.$query.'&order[0][column]=8&order[0][dir]=asc')->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 0);
    }

    public function test_pt_cannot_reach_admin_dpl_mahasiswa_endpoints(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);

        $this->ajax('dpl/listdataserver?draw=1')->assertStatus(302);
        $this->ajax('mahasiswa/listdataserver?draw=1')->assertStatus(302);
        $this->get('dpl/export')->assertStatus(302);
        $this->get('mahasiswa/export')->assertStatus(302);
    }
}
