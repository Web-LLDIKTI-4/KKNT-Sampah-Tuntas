<?php

namespace App\Http\Controllers;

use App\Exports\DplExport;
use App\Http\Requests\Admin\BulkUpdateDplRequest;
use App\Http\Requests\Admin\DplRequest;
use App\Imports\DPLImport;
use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\BulkUpdateService;
use App\Services\PersonDeletionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected function idNumberColumn(): string
    {
        return 'nidn';
    }

    protected function makeImport(): object
    {
        return new DPLImport;
    }

    protected function makeExport(): object
    {
        return new DplExport;
    }

    protected function deleteRecord(Model $record): void
    {
        $this->deletion->deleteDpl($record);
    }

    public function edit(string $id)
    {
        $dpl = Dpl::findOrFail($id);

        return view('dpl.edit', [
            'data' => $dpl,
            'canEditEmail' => ! $dpl->hasAccount(),
            'perguruanTinggi' => Satuanpendidikan::orderBy('nm_lemb')->pluck('nm_lemb', 'npsn'),
            'lokasiProgram' => LokasiProgram::orderBy('nama_lokasi')->pluck('nama_lokasi', 'id'),
        ]);
    }

    // Email hanya diubah bila DPL belum punya akun: kunci relasi ke users, dplmentoring, dpllaporan
    public function update(DplRequest $request)
    {
        DB::transaction(function () use ($request) {
            // Lock row so the email lock is evaluated atomically
            $dpl = Dpl::lockForUpdate()->findOrFail($request->validated('id_dpl'));
            $hasAccount = $dpl->hasAccount();
            $fields = ['nidn', 'nama', 'phone', 'prodi', 'kodept', 'location_program'];
            if (! $hasAccount) {
                $fields[] = 'email';
            }

            $dpl->update($request->safe()->only($fields));

            // Keep linked DPL account name & location in sync (login checks users.location_program)
            if ($hasAccount) {
                User::where('email', $dpl->email)->where('role', 'dpl')->first()?->forceFill(['name' => $dpl->nama, 'location_program' => $dpl->location_program])->save();
            }
        });

        // Cache peta/sebaran di-flush oleh PetaSebaranCacheObserver (model Dpl)

        return $this->saved('Data DPL berhasil diupdate');
    }

    public function bulkUpdate(BulkUpdateDplRequest $request, BulkUpdateService $service)
    {
        return $service->updatePersons(Dpl::class, 'dpl', $request->validated('ids'), $request->fields())->toResponse();
    }
}
