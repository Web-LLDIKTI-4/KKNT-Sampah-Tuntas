<?php

namespace App\Http\Controllers;

use App\Imports\DPLImport;
use App\Models\Dpl;
use App\Services\PersonDeletionService;
use Illuminate\Database\Eloquent\Model;

class DplController extends PersonMasterController
{
    public function __construct(private PersonDeletionService $deletion) {}

    protected function model(): string
    {
        return Dpl::class;
    }

    protected function viewPrefix(): string
    {
        return 'dpl';
    }

    protected function label(): string
    {
        return 'DPL';
    }

    protected function makeImport(): object
    {
        return new DPLImport;
    }

    protected function deleteRecord(Model $record): void
    {
        $this->deletion->deleteDpl($record);
    }
}
