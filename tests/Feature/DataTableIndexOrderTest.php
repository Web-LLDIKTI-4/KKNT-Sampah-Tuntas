<?php

namespace Tests\Feature;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DataTableIndexOrderTest extends TestCase
{
    use RefreshDatabase;

    public static function endpoints(): array
    {
        return [
            'dpl' => ['dpl/listdataserver', Dpl::class],
            'mahasiswa' => ['mahasiswa/listdataserver', Mahasiswa::class],
        ];
    }

    // Default DataTables JS: order kolom 0 (DT_RowIndex) tidak boleh menjadi ORDER BY tabel.DT_RowIndex
    #[DataProvider('endpoints')]
    public function test_order_on_index_column_is_ignored(string $url, string $model): void
    {
        $this->loginAs('admin');
        $model::factory()->count(2)->create();

        $this->getJson($url.'?draw=1&start=0&length=10'
            .'&columns[0][data]=DT_RowIndex&columns[0][name]=DT_RowIndex&columns[0][searchable]=false&columns[0][orderable]=true'
            .'&order[0][column]=0&order[0][dir]=asc', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('data.0.DT_RowIndex', 1);
    }
}
