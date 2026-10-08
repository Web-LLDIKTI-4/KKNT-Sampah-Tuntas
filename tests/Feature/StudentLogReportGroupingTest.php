<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\PendataanPemilahanSampah;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Search & sort tabel grouping per mahasiswa (StudentLogReportController) di semua menu turunan
class StudentLogReportGroupingTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    public static function menus(): array
    {
        return [
            'log harian' => ['admlogkegiatan', Logkegiatan::class],
            'pemilahan' => ['pendataanpemilahan', PendataanPemilahanSampah::class],
            'log bulanan' => ['admlogbulanan', Logbulanan::class],
            'kehadiran' => ['admlogkehadiran', Kehadiran::class],
        ];
    }

    private function columns(array $search = []): array
    {
        $cols = [];
        foreach (['DT_RowIndex', 'nim', 'nama_mahasiswa', 'email', 'nm_lemb', 'count_log'] as $i => $c) {
            $cols[$i] = [
                'data' => $c, 'name' => $c,
                'searchable' => in_array($c, ['DT_RowIndex', 'count_log']) ? 'false' : 'true',
                'orderable' => $c === 'DT_RowIndex' ? 'false' : 'true',
                'search' => ['value' => $search[$c] ?? '', 'regex' => 'false'],
            ];
        }

        return $cols;
    }

    private function grouping(string $prefix, array $params)
    {
        return $this->getJson($prefix.'/listdatagrouping?'.http_build_query($params + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $this->columns(),
        ]), $this->ajax)->assertOk()->assertJsonMissingPath('error');
    }

    private function seedStudents(): void
    {
        $sp = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Zebrawangi']);
        Mahasiswa::factory()->create(['email' => 'x1@pps.test', 'nama' => 'Qwertyanto', 'nim' => '99887766', 'kodept' => $sp->npsn]);
        Mahasiswa::factory()->create(['email' => 'x2@pps.test', 'nama' => 'Lainnya', 'nim' => '11223344']);
    }

    #[DataProvider('menus')]
    public function test_global_search_per_kolom_memfilter(string $prefix): void
    {
        $this->loginAs('admin');
        $this->seedStudents();

        foreach (['Qwerty', 'Zebrawangi', 'x1@pps', '998877'] as $keyword) {
            $this->grouping($prefix, ['search' => ['value' => $keyword, 'regex' => 'false']])
                ->assertJsonPath('recordsTotal', 2)
                ->assertJsonPath('recordsFiltered', 1);
        }
        $this->grouping($prefix, ['search' => ['value' => 'tidakadasiapapun', 'regex' => 'false']])
            ->assertJsonPath('recordsFiltered', 0);
    }

    #[DataProvider('menus')]
    public function test_search_kolom_individual_nama(string $prefix): void
    {
        $this->loginAs('admin');
        $this->seedStudents();

        $this->grouping($prefix, ['columns' => $this->columns(['nama_mahasiswa' => 'Qwerty'])])
            ->assertJsonPath('recordsFiltered', 1);
    }

    #[DataProvider('menus')]
    public function test_sort_nama_email_pt(string $prefix): void
    {
        $this->loginAs('admin');
        foreach (['Zulkifli' => 'Univ C', 'Andi' => 'Univ B', 'Mawar' => 'Univ A'] as $nama => $pt) {
            $sp = Satuanpendidikan::factory()->create(['nm_lemb' => $pt]);
            Mahasiswa::factory()->create(['email' => strtolower($nama).'@pps.test', 'nama' => $nama, 'kodept' => $sp->npsn]);
        }

        $first = fn (int $col, string $dir, string $key) => (string) $this->grouping($prefix, ['order' => [['column' => $col, 'dir' => $dir]]])->json("data.0.$key");

        $this->assertStringContainsString('Andi', $first(2, 'asc', 'nama_mahasiswa'));
        $this->assertStringContainsString('Zulkifli', $first(2, 'desc', 'nama_mahasiswa'));
        $this->assertSame('andi@pps.test', $first(3, 'asc', 'email'));
        $this->assertSame('Univ A', $first(4, 'asc', 'nm_lemb'));
    }

    #[DataProvider('menus')]
    public function test_sort_jumlah_log(string $prefix, string $model): void
    {
        $this->loginAs('admin');
        $sedikit = Mahasiswa::factory()->create(['email' => 'sedikit@pps.test']);
        $banyak = Mahasiswa::factory()->create(['email' => 'banyak@pps.test']);
        $model::factory()->create(['email' => $sedikit->email]);
        $model::factory()->count(3)->create(['email' => $banyak->email]);

        $this->grouping($prefix, ['order' => [['column' => 5, 'dir' => 'desc']]])
            ->assertJsonPath('data.0.email', 'banyak@pps.test')
            ->assertJsonPath('data.0.count_log', 3);
    }

    #[DataProvider('menus')]
    public function test_search_tetap_dalam_scope_dpl(string $prefix): void
    {
        $dpl = $this->loginAs('dpl');
        $this->seedStudents();
        \App\Models\Dplmentoring::create(['email_mahasiswa' => 'x2@pps.test', 'email_dpl' => $dpl->email]);

        // Qwertyanto bukan bimbingan → tidak boleh muncul meski cocok keyword
        $this->grouping($prefix, ['search' => ['value' => 'Qwerty', 'regex' => 'false']])
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 0);
    }

    public function test_keyword_wildcard_dan_kutip_aman(): void
    {
        $this->loginAs('admin');
        $this->seedStudents();

        foreach (["' OR 1=1 -- ", '") or ("1"="1'] as $keyword) {
            $this->grouping('admlogkegiatan', ['search' => ['value' => $keyword, 'regex' => 'false']])
                ->assertJsonPath('recordsFiltered', 0);
        }
    }
}
