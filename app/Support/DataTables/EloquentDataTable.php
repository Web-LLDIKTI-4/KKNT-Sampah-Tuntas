<?php

namespace App\Support\DataTables;

use Yajra\DataTables\EloquentDataTable as BaseEloquentDataTable;

class EloquentDataTable extends BaseEloquentDataTable
{
    use IgnoresIndexColumnOrder;
}
