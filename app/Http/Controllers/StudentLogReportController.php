<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Facades\DataTables;

/**
 * Laporan log per mahasiswa untuk admin/DPL/PT.
 * Akses dibatasi oleh Mahasiswa::visibleTo() (admin semua, DPL bimbingan, PT mahasiswanya).
 */
abstract class StudentLogReportController extends Controller
{
    use RespondsWithJson;

    abstract protected function viewPrefix(): string;

    abstract protected function routePrefix(): string;

    // Relasi log di model Mahasiswa yang dihitung, mis. "logkegiatan"
    abstract protected function logRelation(): string;

    // DataTable detail log satu mahasiswa
    abstract protected function detailTable(string $email): DataTableAbstract;

    abstract protected function exportFor(string $email);

    protected function constrainCountedLogs($query): void
    {
        $query->whereNotNull('deskripsi');
    }

    protected function showGroupActionColumn(): bool
    {
        return true;
    }

    protected function groupedStudentName(Mahasiswa $student): string
    {
        return $student->nama ?? 'Nama tidak tersedia';
    }

    public function index()
    {
        return view($this->viewPrefix().'.index');
    }

    public function listdatagroup()
    {
        return view($this->viewPrefix().'.listdatagroup');
    }

    public function listdatagrouping(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = Mahasiswa::visibleTo($request->user())
            ->with('sp')
            ->withCount([$this->logRelation().' as count_log' => fn ($query) => $this->constrainCountedLogs($query)])
            ->unless($this->userSorts(), fn ($q) => $q->orderByDesc('created_at'));

        $table = DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => $row->nim ?? 'NIM tidak tersedia')
            ->addColumn('nama_mahasiswa', fn ($row) => $this->groupedStudentName($row))
            ->addColumn('email', fn ($row) => $row->email ?? 'Email tidak tersedia')
            ->addColumn('nm_lemb', fn ($row) => $row->sp->nm_lemb ?? 'Perguruan Tinggi tidak tersedia')
            ->addColumn('count_log', fn ($row) => $row->count_log)
            ->orderColumn('count_log', 'count_log $1')
            // Kolom hasil addColumn dipetakan ke kolom DB agar search & sort jalan
            ->filterColumn('nim', fn ($q, $k) => $q->where('nim', 'like', "%{$k}%"))
            ->orderColumn('nim', 'nim $1')
            ->filterColumn('nama_mahasiswa', fn ($q, $k) => $q->where('nama', 'like', "%{$k}%"))
            ->orderColumn('nama_mahasiswa', 'nama $1')
            ->filterColumn('email', fn ($q, $k) => $q->where('email', 'like', "%{$k}%"))
            ->orderColumn('email', 'email $1')
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->whereHas('sp', fn ($sp) => $sp->where('nm_lemb', 'like', "%{$k}%")))
            ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy(
                Satuanpendidikan::select('nm_lemb')->whereColumn('npsn', 'mahasiswa.kodept')->limit(1),
                $order
            ));

        if ($this->showGroupActionColumn()) {
            $table->addColumn('action', fn ($row) => ActionButtons::make(
                urlView: url($this->routePrefix().'/listdata/'.rawurlencode($row->email)),
            ));
        }

        return $table->rawColumns($this->showGroupActionColumn() ? ['action'] : ['nama_mahasiswa'])->make(true);
    }

    public function listdata()
    {
        return view($this->viewPrefix().'.listdata');
    }

    public function listdataserver(Request $request, string $email)
    {
        abort_unless($request->ajax(), 404);
        $this->authorizeStudent($request, $email);

        return $this->detailTable($email)->make(true);
    }

    public function export(Request $request, string $email)
    {
        $this->authorizeStudent($request, $email);

        return $this->exportFor($email);
    }

    // True bila request DataTables membawa sort kolom (urutan default jangan mendahului)
    protected function userSorts(): bool
    {
        $request = app('datatables.request');

        return collect($request->orderableColumns())
            ->contains(fn ($order) => $request->columnName((int) $order['column']) !== 'DT_RowIndex');
    }

    // Search tanggal format d-m-Y (tampilan) → Y-m-d (DB)
    protected function filterTanggal($query, string $keyword, string $column = 'tanggal'): void
    {
        $query->where($column, 'like', '%'.implode('-', array_reverse(explode('-', trim($keyword)))).'%');
    }

    protected function authorizeStudent(Request $request, ?string $email): void
    {
        abort_unless(Mahasiswa::canBeViewedBy($request->user(), $email), 404);
    }
}
