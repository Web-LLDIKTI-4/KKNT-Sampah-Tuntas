<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmlaporandplGroupTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();

        $spX = Satuanpendidikan::factory()->create(['nm_lemb' => 'Univ Xenon']);
        $spY = Satuanpendidikan::factory()->create(['nm_lemb' => 'Univ Yttrium 100%']);
        // Andi: 3 laporan, Budi: 1 laporan, Citra: 0 laporan & tanpa PT, Dpl tanpa akun user
        $this->dpl('Andi Dosen', $spX->npsn, 3);
        $this->dpl('Budi Dosen', $spY->npsn, 1);
        $this->dpl('Citra Dosen', null, 0);
        Dpl::factory()->create(['kodept' => null]);
    }

    private function dpl(string $name, ?string $kodept, int $laporan): Dpl
    {
        $user = User::factory()->role('dpl')->create(['name' => $name]);
        $dpl = Dpl::factory()->create(['email' => $user->email, 'kodept' => $kodept]);
        Dpllaporan::factory()->count($laporan)->create(['email' => $dpl->email]);

        return $dpl;
    }

    private function list(array $extra = []): array
    {
        $cols = [];
        foreach (['DT_RowIndex' => false, 'nama_dpl' => true, 'nama_pt' => true, 'count_log' => false, 'action' => false] as $c => $searchable) {
            $cols[] = ['data' => $c, 'name' => $c, 'searchable' => $searchable ? 'true' : 'false',
                'orderable' => in_array($c, ['DT_RowIndex', 'action'], true) ? 'false' : 'true', 'search' => ['value' => '']];
        }
        $res = $this->getJson('admlaporandpl/listdatagrouping?'.http_build_query($extra + ['draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $cols]), $this->ajax)
            ->assertOk();
        $this->assertNull($res->json('error'));

        return $res->json('data');
    }

    private function names(array $rows): array
    {
        return array_column($rows, 'nama_dpl');
    }

    public function test_jumlah_laporan_dan_null_safe(): void
    {
        $this->loginAs('admin');
        $rows = collect($this->list())->keyBy('nama_dpl');

        $this->assertCount(4, $rows);
        $this->assertSame([3, 1, 0], [(int) $rows['Andi Dosen']['count_log'], (int) $rows['Budi Dosen']['count_log'], (int) $rows['Citra Dosen']['count_log']]);
        $this->assertSame('Univ Xenon', $rows['Andi Dosen']['nama_pt']);
        $this->assertSame('Nama Perguruan Tinggi Tidak Tersedia', $rows['Citra Dosen']['nama_pt']);
        $this->assertArrayHasKey('Nama DPL Tidak Tersedia', $rows->all());
    }

    public function test_search_nama_dpl_dan_nama_pt(): void
    {
        $this->loginAs('admin');

        $this->assertSame(['Andi Dosen'], $this->names($this->list(['search' => ['value' => 'Andi']])));
        $this->assertSame(['Budi Dosen'], $this->names($this->list(['search' => ['value' => 'Yttrium']])));
        // "%" literal, bukan wildcard
        $this->assertSame(['Budi Dosen'], $this->names($this->list(['search' => ['value' => '100%']])));
        $this->assertSame([], $this->list(['search' => ['value' => 'Xenon%Dosen']]));
        $this->assertSame([], $this->list(['search' => ['value' => 'zzqq']]));
    }

    public function test_sort_nama_dpl_nama_pt_dan_jumlah(): void
    {
        $this->loginAs('admin');

        $byName = $this->names($this->list(['order' => [['column' => 1, 'dir' => 'asc']]]));
        $this->assertSame(['Andi Dosen', 'Budi Dosen', 'Citra Dosen'], array_values(array_diff($byName, ['Nama DPL Tidak Tersedia'])));
        $byName = $this->names($this->list(['order' => [['column' => 1, 'dir' => 'desc']]]));
        $this->assertSame(['Citra Dosen', 'Budi Dosen', 'Andi Dosen'], array_values(array_diff($byName, ['Nama DPL Tidak Tersedia'])));

        $byPt = $this->names($this->list(['order' => [['column' => 2, 'dir' => 'desc']]]));
        $this->assertSame(['Budi Dosen', 'Andi Dosen'], array_slice($byPt, 0, 2));

        $byCount = $this->list(['order' => [['column' => 3, 'dir' => 'desc']]]);
        $this->assertSame([3, 1, 0, 0], array_map('intval', array_column($byCount, 'count_log')));
    }

    public function test_length_minus_one_dicap_100(): void
    {
        $this->loginAs('admin');
        Dpl::factory()->count(101)->create();

        $this->assertCount(100, $this->list(['length' => -1]));
    }

    // Kolom dari request di luar kolom view (relasi/kolom DB mentah) tidak boleh jadi kolom SQL
    public function test_kolom_crafted_diabaikan_tanpa_500_dan_tanpa_oracle(): void
    {
        $this->loginAs('admin');
        $endpoints = [
            'admlaporandpl/listdatagrouping' => ['user.password', 'kolom_tidak_ada', 'email'],
            'admfreeform/listdataserver' => ['mahasiswa.email', 'kolom_tidak_ada', 'id_mahasiswa'],
            'admstructureform/listdataserver' => ['mahasiswa.email', 'kolom_tidak_ada', 'id_mahasiswa'],
            'admevaluasikegiatan/listdataserver' => ['evaluasikegiatan.pertanyaan', 'kolom_tidak_ada', 'id_evaluasi'],
        ];

        foreach ($endpoints as $uri => $crafted) {
            foreach ($crafted as $kolom) {
                $cols = [['data' => $kolom, 'name' => $kolom, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']]];
                $res = $this->getJson($uri.'?'.http_build_query([
                    'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $cols,
                    'order' => [['column' => 0, 'dir' => 'asc']], 'search' => ['value' => '$2y$'],
                ]), $this->ajax);
                $this->assertSame(200, $res->status(), "$uri $kolom");
                $this->assertNull($res->json('error'), "$uri $kolom");
                // Kolom crafted tidak ikut search: hash bcrypt tidak bisa dipakai sebagai oracle
                $this->assertSame((int) $res->json('recordsTotal'), (int) $res->json('recordsFiltered'), "$uri $kolom");
            }
        }
    }

    public function test_non_admin_ditolak(): void
    {
        foreach (['dpl', 'pt', 'kepala'] as $role) {
            $this->loginAs($role);
            $this->assertTrue($this->getJson('admlaporandpl/listdatagrouping?draw=1', $this->ajax)->isRedirect(), $role);
        }
    }
}
