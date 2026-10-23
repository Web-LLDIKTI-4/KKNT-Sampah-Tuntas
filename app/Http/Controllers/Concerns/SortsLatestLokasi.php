<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Mahasiswa;
use Yajra\DataTables\EloquentDataTable;

// Sort & search for Mahasiswa::withLatestLokasi() aliases; requires HandlesDataTablesOrder
trait SortsLatestLokasi
{
    protected function latestLokasiColumns(EloquentDataTable $dataTable): EloquentDataTable
    {
        // WHERE can't reference a select alias, so search repeats the subquery
        foreach (Mahasiswa::LOKASI_COLUMNS as $alias => $column) {
            $dataTable
                ->filterColumn($alias, fn ($q, $k) => $q->where(Mahasiswa::latestLokasi($column), 'like', $this->likeContains($k)))
                ->orderColumn($alias, $alias.' $1');
        }

        return $dataTable;
    }
}
