<?php

namespace App\Http\Requests\Admin;

// Export is fetched as a blob, so validation errors need a non-200 status
class ExportSelectedRequest extends BulkSelectionRequest
{
    protected int $errorStatus = 422;
}
