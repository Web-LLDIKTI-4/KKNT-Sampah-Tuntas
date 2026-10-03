<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;

/**
 * DataTable server-side untuk halaman pilih massal: filter PT/lokasi wajib, checkbox createuser[] per baris.
 */
class BulkSelectDataTable
{
    /** @param  array<int, string>  $columns */
    public static function make(Builder $query, array $columns, Request $request): EloquentDataTable
    {
        $table = $query->getModel()->getTable();
        $kodept = is_string($request->input('kodept')) ? trim($request->input('kodept')) : '';
        $lokasi = is_string($request->input('location_program')) ? trim($request->input('location_program')) : '';

        // Tanpa filter → kosong, supaya tidak memuat seluruh data
        $query->when($kodept === '' && $lokasi === '', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($kodept !== '', fn ($q) => $q->where("{$table}.kodept", $kodept))
            ->when($lokasi !== '', fn ($q) => $q->where("{$table}.location_program", $lokasi));

        return PersonDataTable::make($query, $columns)
            ->addColumn('pilih', fn ($row) => '<input type="checkbox" name="createuser[]" value="'.e($row->email).'">')
            ->rawColumns(['pilih']);
    }
}
