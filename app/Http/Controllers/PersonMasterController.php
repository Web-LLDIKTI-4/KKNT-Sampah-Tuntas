<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\ImportFileRequest;
use App\Support\ActionButtons;
use App\Support\PersonDataTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Master data orang (mahasiswa/DPL): listing, import Excel, hapus berantai.
 */
abstract class PersonMasterController extends Controller
{
    use RespondsWithJson;

    /** @return class-string<Model> */
    abstract protected function model(): string;

    abstract protected function viewPrefix(): string;

    abstract protected function label(): string;

    /** @return array<int, string> kolom tabel yang ditampilkan di listing */
    abstract protected function listColumns(): array;

    abstract protected function makeImport(): object;

    abstract protected function deleteRecord(Model $record): void;

    public function index()
    {
        return view($this->viewPrefix().'.index');
    }

    public function listdata()
    {
        return view($this->viewPrefix().'.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $key = (new ($this->model()))->getKeyName();

        return PersonDataTable::make($this->model()::query(), [$key, ...$this->listColumns()])
            ->addColumn('action', fn ($row) => ActionButtons::make(
                urlDelete: url($this->viewPrefix().'/destroy'),
                idField: $key,
                idValue: $row->{$key},
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function import()
    {
        return view($this->viewPrefix().'.import');
    }

    public function prosesimport(ImportFileRequest $request)
    {
        try {
            $import = $this->makeImport();
            Excel::import($import, $request->file('file'));
        } catch (Throwable $e) {
            Log::error('Import '.$this->label().' gagal', ['exception' => $e]);

            return $this->failed('Terjadi kesalahan saat mengimpor data. Periksa format file sesuai template.');
        }

        $failed = count($import->errors);
        if ($failed > 0) {
            // Batasi agar respons tetap kecil untuk file besar
            $errors = array_slice($import->errors, 0, 100);
            if ($failed > 100) {
                $errors[] = 'dan '.($failed - 100).' baris lainnya.';
            }

            return response()->json([
                'success' => true,
                'toast' => $import->imported > 0 ? 'warning' : 'error',
                'message' => $import->imported > 0
                    ? "{$import->imported} data berhasil diimpor, {$failed} baris dilewati."
                    : "Tidak ada data yang diimpor, {$failed} baris dilewati.",
                'import_errors' => $errors,
            ]);
        }

        return $this->saved("{$import->imported} data ".$this->label().' berhasil diimpor.');
    }

    public function destroy(Request $request)
    {
        $key = (new ($this->model()))->getKeyName();
        $record = $this->model()::find($request->input($key));
        if (! $record) {
            return $this->failed($this->label().' tidak ditemukan');
        }

        try {
            $this->deleteRecord($record);
        } catch (Throwable $e) {
            Log::error('Hapus '.$this->label().' gagal', ['exception' => $e]);

            return $this->failed('Data gagal dihapus');
        }

        return $this->deleted();
    }
}
