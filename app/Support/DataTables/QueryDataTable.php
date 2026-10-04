<?php

namespace App\Support\DataTables;

use Yajra\DataTables\QueryDataTable as BaseQueryDataTable;

class QueryDataTable extends BaseQueryDataTable
{
    use IgnoresIndexColumnOrder;
}
