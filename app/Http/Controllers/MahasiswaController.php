<?php

namespace App\Http\Controllers;

use App\Imports\ImportMahasiswa;
use App\Models\Mahasiswa;
use App\Services\PersonDeletionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MahasiswaController extends PersonMasterController
{
    public function __construct(private PersonDeletionService $deletion) {}

    protected function model(): string
    {
        return Mahasiswa::class;
    }

    protected function viewPrefix(): string
    {
        return 'mahasiswa';
    }

    protected function label(): string
    {
        return 'mahasiswa';
    }

    // ketua_kelompok: EXISTS pj_desa per baris dalam 1 query (tanpa N+1)
    protected function listQuery(): Builder
    {
        return parent::listQuery()->withExists('pjdesa as ketua_kelompok');
    }

    protected function makeImport(): object
    {
        return new ImportMahasiswa;
    }

    protected function deleteRecord(Model $record): void
    {
        $this->deletion->deleteMahasiswa($record);
    }
}
