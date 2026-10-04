<?php

namespace App\Support\DataTables;

trait IgnoresIndexColumnOrder
{
    // Kolom nomor urut bukan kolom SQL; order padanya diabaikan agar tidak jadi ORDER BY tabel.DT_RowIndex
    protected function defaultOrdering(): void
    {
        $index = (string) config('datatables.index_column', 'DT_RowIndex');
        $this->columnDef['order'][$index] ??= ['sql' => false, 'bindings' => []];

        parent::defaultOrdering();
    }
}
