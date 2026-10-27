<?php

namespace App\Http\Controllers;

use App\Exports\MahasiswaExport;
use App\Http\Controllers\Concerns\SortsLatestLokasi;
use App\Http\Requests\Admin\BulkUpdateMahasiswaRequest;
use App\Http\Requests\Admin\MahasiswaRequest;
use App\Imports\ImportMahasiswa;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\BulkUpdateService;
use App\Services\PersonDeletionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;

class MahasiswaController extends PersonMasterController
{
    use SortsLatestLokasi;

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

    protected function idNumberColumn(): string
    {
        return 'nim';
    }

    // ketua_kelompok: EXISTS pj_desa per baris dalam 1 query (tanpa N+1)
    protected function listQuery(): Builder
    {
        return parent::listQuery()
            ->withExists('pjdesa as ketua_kelompok')
            ->withLatestLokasi()
            ->lokasiStatus(request()->query('lokasi_status'))
            ->ketuaKelompok(request()->query('ketua_kelompok'));
    }

    protected function extraOrderColumns(): array
    {
        return array_keys(Mahasiswa::LOKASI_COLUMNS);
    }

    protected function customizeDataTable(EloquentDataTable $dataTable): EloquentDataTable
    {
        return $this->latestLokasiColumns($dataTable);
    }

    protected function makeImport(): object
    {
        return new ImportMahasiswa;
    }

    protected function makeExport(): object
    {
        return new MahasiswaExport;
    }

    protected function deleteRecord(Model $record): void
    {
        $this->deletion->deleteMahasiswa($record);
    }

    public function edit(string $id)
    {
        $mahasiswa = Mahasiswa::findOrFail($id);

        return view('mahasiswa.edit', [
            'data' => $mahasiswa,
            'canEditEmail' => ! $mahasiswa->isEmailLocked(),
            'perguruanTinggi' => Satuanpendidikan::orderBy('nm_lemb')->pluck('nm_lemb', 'npsn'),
            'lokasiProgram' => LokasiProgram::orderBy('nama_lokasi')->pluck('nama_lokasi', 'id'),
        ]);
    }

    // Email hanya diubah bila belum terkunci (akun, pj_desa, dpl_mentoring)
    public function update(MahasiswaRequest $request)
    {
        DB::transaction(function () use ($request) {
            // Lock row so the email lock is evaluated atomically
            $mahasiswa = Mahasiswa::lockForUpdate()->findOrFail($request->validated('id_mahasiswa'));
            $hasAccount = $mahasiswa->hasAccount();
            $fields = ['nim', 'tahun_masuk', 'nama', 'phone', 'prodi', 'kodept', 'location_program'];
            if (! $mahasiswa->isEmailLocked()) {
                $fields[] = 'email';
            }

            $mahasiswa->update($request->safe()->only($fields));

            if ($hasAccount) {
                User::where('email', $mahasiswa->email)->where('role', 'mahasiswa')->first()?->forceFill(['name' => $mahasiswa->nama, 'location_program' => $mahasiswa->location_program])->save();
            }
        });

        // Cache peta/capaian di-flush oleh PetaSebaranCacheObserver (model Mahasiswa)
        return $this->saved('Data mahasiswa berhasil diupdate');
    }

    public function bulkUpdate(BulkUpdateMahasiswaRequest $request, BulkUpdateService $service)
    {
        return $service->updatePersons(Mahasiswa::class, 'mahasiswa', $request->validated('ids'), $request->fields())->toResponse();
    }
}
