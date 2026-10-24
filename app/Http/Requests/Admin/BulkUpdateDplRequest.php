<?php

namespace App\Http\Requests\Admin;

use App\Models\Dpl;

class BulkUpdateDplRequest extends BulkUpdatePersonRequest
{
    protected function model(): string
    {
        return Dpl::class;
    }

    protected function identityColumn(): string
    {
        return 'nidn';
    }

    protected function identityLabel(): string
    {
        return 'NIDN';
    }
}
