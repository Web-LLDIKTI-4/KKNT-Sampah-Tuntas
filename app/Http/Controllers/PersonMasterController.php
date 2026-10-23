<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\ImportFileRequest;
use App\Support\ActionButtons;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

/**
 * Master data orang (mahasiswa/DPL): listing, import Excel, hapus berantai.
 */
abstract class PersonMasterController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    /** @return class-string<Model> */
    abstract protected function model(): string;

    abstract protected function viewPrefix(): string;

    abstract protected function label(): string;

    abstract protected function makeImport(): object;

    abstract protected function deleteRecord(Model $record): void;

    // Identity number column shown in the list, e.g. nim / nidn
    abstract protected function idNumberColumn(): string;

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

        $this->sanitizeDataTablesInput();
        $model = new ($this->model());
        $key = $model->getKeyName();
        $whitelist = [$this->idNumberColumn(), 'nama', 'email', 'phone', 'nm_lemb', 'location_program'];

        $query = $this->listQuery()
            ->when(! $this->hasClientOrder([...$whitelist, ...$this->extraOrderColumns()]), fn ($q) => $q->orderBy($model->getTable().'.nama'));

        $dataTable = DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->nama_lokasi ?? 'Belum Terdata')
            // Joined columns: map search & sort to the joined tables
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->where('sp.nm_lemb', 'like', $this->likeContains($k)))
            ->orderColumn('nm_lemb', 'sp.nm_lemb $1')
            ->filterColumn('location_program', fn ($q, $k) => $q->where('lp.nama_lokasi', 'like', $this->likeContains($k)))
            ->orderColumn('location_program', 'lp.nama_lokasi $1')
            ->addColumn('action', fn ($row) => ActionButtons::make(
                urlEdit: $this->editUrl($row),
                urlDelete: url($this->viewPrefix().'/destroy'),
                idField: $key,
                idValue: $row->{$key},
            ))
            ->rawColumns(['action'])
            ->whitelist($whitelist);

        return $this->customizeDataTable($dataTable)->make(true);
    }

    // Subquery-alias columns sorted via orderColumn(); kept out of whitelist so they never hit raw WHERE
    protected function extraOrderColumns(): array
    {
        return [];
    }

    protected function customizeDataTable(EloquentDataTable $dataTable): EloquentDataTable
    {
        return $dataTable;
    }

    // Tombol edit hanya muncul bila subclass mengembalikan URL
    protected function editUrl(Model $record): ?string
    {
        return null;
    }

    // Joins replace eager loads: npsn is unique and lokasi_program.id is PK, so no duplicate rows
    protected function listQuery(): Builder
    {
        $model = new ($this->model());
        $table = $model->getTable();

        return $model->newQuery()
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', $table.'.kodept')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', $table.'.location_program')
            ->select([
                $table.'.'.$model->getKeyName(),
                $table.'.'.$this->idNumberColumn(),
                $table.'.nama',
                $table.'.email',
                $table.'.phone',
                'sp.nm_lemb',
                'lp.nama_lokasi',
            ]);
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
            return response()->json([
                'success' => true,
                'toast' => $import->imported > 0 ? 'warning' : 'error',
                'message' => $import->imported > 0
                    ? "{$import->imported} data berhasil diimpor, {$failed} baris dilewati."
                    : "Tidak ada data yang diimpor, {$failed} baris dilewati.",
                'import_errors' => $import->errors,
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
