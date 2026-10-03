<?php

namespace App\Support;

use Yajra\DataTables\Utilities\Request as DataTablesRequest;

class DataTableOrder
{
    // true bila user memilih urutan kolom; urutan default query hanya dipakai bila false
    public static function requested(): bool
    {
        return (new DataTablesRequest)->orderableColumns() !== [];
    }
}
