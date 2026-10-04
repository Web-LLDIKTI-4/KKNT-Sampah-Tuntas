<?php

namespace App\Support;

use Yajra\DataTables\Utilities\Request as DataTablesRequest;

class DataTableOrder
{
    // true bila user memilih urutan kolom; urutan default query hanya dipakai bila false
    public static function requested(): bool
    {
        $index = (string) config('datatables.index_column', 'DT_RowIndex');

        // Order pada kolom nomor urut tidak dihitung (diabaikan engine), jadi urutan default tetap dipakai
        return collect((new DataTablesRequest)->orderableColumns())
            ->contains(fn ($order) => ! in_array($index, [
                request()->input("columns.{$order['column']}.data"),
                request()->input("columns.{$order['column']}.name"),
            ], true));
    }
}
