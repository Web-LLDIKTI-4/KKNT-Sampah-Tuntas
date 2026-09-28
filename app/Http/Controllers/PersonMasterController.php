<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\ImportFileRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

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

        return DataTables::of($this->model()::with(['sp', 'locationProgram'])->get())
            ->addIndexColumn()
            ->addColumn('nm_lemb', fn ($row) => $row->sp->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->locationProgram->nama_lokasi ?? 'Belum Terdata')
            ->addColumn('action', fn ($row) => view('components.action-data', [
                'urlDelete' => url($this->viewPrefix().'/destroy'),
                'idField' => $key,
                'idValue' => $row->{$key},
            ])->render())
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

            return back()->with('error', 'Terjadi kesalahan saat mengimpor data. Periksa format file sesuai template.');
        }

        if (count($import->errors) > 0) {
            return back()
                ->with('warning', "{$import->imported} data berhasil diimpor.")
                ->with('import_errors', $import->errors);
        }

        return back()->with('success', 'Data '.$this->label().' berhasil diimpor.');
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
