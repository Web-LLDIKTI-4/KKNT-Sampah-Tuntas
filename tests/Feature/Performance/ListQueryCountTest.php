<?php

namespace Tests\Feature\Performance;

use App\Models\Dpl;
use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Freeform;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Baseline optimize-mvc batch 0: jumlah query tiap listing P0 harus konstan (tidak tumbuh dengan N)
 * dan paginasi DataTables harus terjadi di SQL (LIMIT), bukan memuat seluruh tabel ke memori.
 */
class ListQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private const N = 30;

    // Auth + role middleware + count + data + beberapa eager load; jauh di bawah N
    private const MAX_QUERIES = 15;

    private const DT_QUERY = '?draw=1&start=0&length=10';

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private array $queries = [];

    private function seedMahasiswa(int $count = self::N, array $attributes = []): Collection
    {
        $sps = Satuanpendidikan::factory()->count(3)->create();
        $lokasi = LokasiProgram::factory()->count(3)->create();

        return collect(range(1, $count))->map(fn ($i) => Mahasiswa::factory()->create($attributes + [
            'kodept' => $sps[$i % 3]->npsn,
            'location_program' => $lokasi[$i % 3]->id,
        ]));
    }

    private function seedDpl(int $count = self::N): Collection
    {
        $sps = Satuanpendidikan::factory()->count(3)->create();
        $lokasi = LokasiProgram::factory()->count(3)->create();

        return collect(range(1, $count))->map(fn ($i) => Dpl::factory()->create([
            'kodept' => $sps[$i % 3]->npsn,
            'location_program' => $lokasi[$i % 3]->id,
        ]));
    }

    private function measure(callable $request): TestResponse
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $request();
        $this->queries = DB::getQueryLog();
        DB::disableQueryLog();

        return $response;
    }

    private function datatable(string $uri): TestResponse
    {
        return $this->measure(fn () => $this->getJson($uri.self::DT_QUERY, self::AJAX));
    }

    private function assertQueryCountConstant(): void
    {
        $this->assertLessThanOrEqual(self::MAX_QUERIES, count($this->queries),
            'Jumlah query '.count($this->queries).' untuk N='.self::N.' (indikasi N+1). Query:'.PHP_EOL
            .collect($this->queries)->pluck('query')->countBy()->sortDesc()->take(5)->map(fn ($c, $q) => "{$c}x {$q}")->implode(PHP_EOL));
    }

    private function assertPaginatedInSql(string $table): void
    {
        $paginated = collect($this->queries)->pluck('query')
            ->contains(fn ($q) => preg_match('/from `'.$table.'`.*\blimit\b/is', $q));

        $this->assertTrue($paginated, "Query ke `{$table}` tanpa LIMIT: seluruh tabel dimuat lalu dipaginasi di PHP");
    }

    private function assertValidDatatable(TestResponse $response, int $total = self::N): void
    {
        $response->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
            ->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsTotal', $total)
            ->assertJsonCount(10, 'data');
    }

    // ---- P0 #1 user ----

    // MERAH: endpoint belum ada (batch 2 membuat user/listdataserver)
    public function test_p0_1_user_listdataserver_is_paginated_with_constant_queries(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa()->each(fn ($m) => User::factory()->role('mahasiswa')->create(['email' => $m->email]));

        $response = $this->datatable('user/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('users');
    }

    // MERAH: N+1 mahasiswa/dpl/pt + sp per baris di user/list.blade.php
    public function test_p0_1_user_listdata_html_has_constant_queries(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa()->each(fn ($m) => User::factory()->role('mahasiswa')->create(['email' => $m->email]));

        $this->measure(fn () => $this->get('user/listdata'))->assertOk();

        $this->assertQueryCountConstant();
    }

    // ---- P0 #2 PersonMaster ----

    // MERAH (LIMIT): Model::with()->get() memuat seluruh tabel
    public function test_p0_2_mahasiswa_listdataserver(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa();

        $response = $this->datatable('mahasiswa/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('mahasiswa');
    }

    // MERAH (LIMIT)
    public function test_p0_2_dpl_listdataserver(): void
    {
        $this->loginAs('admin');
        $this->seedDpl();

        $response = $this->datatable('dpl/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('dpl');
    }

    // ---- P0 #3 Ptmahasiswa / Ptdpl ----

    // MERAH (LIMIT)
    public function test_p0_3_ptmahasiswa_listdataserver(): void
    {
        $this->loginAs('kepala');
        $this->seedMahasiswa();

        $response = $this->datatable('ptmahasiswa/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('mahasiswa');
    }

    // MERAH (LIMIT)
    public function test_p0_3_ptdpl_listdataserver(): void
    {
        $this->loginAs('kepala');
        $this->seedDpl();

        $response = $this->datatable('ptdpl/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('dpl');
    }

    // ---- P0 #4 Admlogharian ----

    // MERAH: N+1 sp + 1 COUNT per baris, tanpa LIMIT
    public function test_p0_4_admlogharian_listdataserver(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa()->each(fn ($m) => Logkegiatan::factory()->create(['email' => $m->email]));

        $response = $this->datatable('admlogharian/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('mahasiswa');
    }

    // ---- P0 #6 jawaban evaluasi ----

    // MERAH (LIMIT)
    public function test_p0_6_admevaluasikegiatan_listdataserver(): void
    {
        $this->loginAs('admin');
        $evaluasi = Evaluasikegiatan::factory()->create();
        $sps = Satuanpendidikan::factory()->count(3)->create();
        foreach (range(1, self::N) as $i) {
            Evaluasikegiatanjawaban::create([
                'id_evaluasi' => $evaluasi->id_evaluasi,
                'jawaban' => 'Ya '.$i,
                'tahun' => date('Y'),
                'kodept' => $sps[$i % 3]->npsn,
                'user' => 'pt'.$i,
            ]);
        }

        $response = $this->datatable('admevaluasikegiatan/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('evaluasi_kegiatan_jawaban');
    }

    // ---- P0 #7 structureform / freeform ----

    // MERAH: N+1 mahasiswa.sp + tanpa LIMIT
    public function test_p0_7_admstructureform_listdataserver(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa()->each(fn ($m) => Nilaikonversi::factory()->create(['id_mahasiswa' => $m->id_mahasiswa]));

        $response = $this->datatable('admstructureform/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('nilai_konversi');
    }

    // MERAH: N+1 mahasiswa.sp + tanpa LIMIT
    public function test_p0_7_admfreeform_listdataserver(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa()->each(fn ($m) => Freeform::factory()->create(['id_mahasiswa' => $m->id_mahasiswa]));

        $response = $this->datatable('admfreeform/listdataserver');

        $this->assertValidDatatable($response);
        $this->assertQueryCountConstant();
        $this->assertPaginatedInSql('nilai_freeform');
    }

    // ---- P0 #5 halaman bulk select (HTML, client-side) ----

    // MERAH: N+1 locationProgram di user/listmember.blade.php
    public function test_p0_5_user_getdatamember_has_constant_queries(): void
    {
        $this->loginAs('admin');
        $this->seedMahasiswa();

        $this->measure(fn () => $this->get('user/getdatamember'))->assertOk();

        $this->assertQueryCountConstant();
    }

    // MERAH: N+1 locationProgram di user/listdpl.blade.php
    public function test_p0_5_user_adduser_has_constant_queries(): void
    {
        $this->loginAs('admin');
        $this->seedDpl();

        $this->measure(fn () => $this->get('user/adduser'))->assertOk();

        $this->assertQueryCountConstant();
    }

    // MERAH: N+1 locationProgram di mentoring/tambah.blade.php
    public function test_p0_5_dplmentoring_tambah_has_constant_queries(): void
    {
        $this->loginAs('dpl');
        $this->seedMahasiswa();

        $this->measure(fn () => $this->get('dplmentoring/tambah'))->assertOk();

        $this->assertQueryCountConstant();
    }
}
