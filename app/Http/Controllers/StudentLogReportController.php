<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\Mahasiswa;
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
            ->withCount($this->logRelation())
            ->orderByDesc('created_at');
        $countColumn = $this->logRelation().'_count';

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => $row->nim ?? 'NIM tidak tersedia')
            ->addColumn('nama_mahasiswa', fn ($row) => $row->nama ?? 'Nama tidak tersedia')
            ->addColumn('email', fn ($row) => $row->email ?? 'Email tidak tersedia')
            ->addColumn('nm_lemb', fn ($row) => $row->sp->nm_lemb ?? 'Perguruan Tinggi tidak tersedia')
            ->addColumn('count_log', fn ($row) => $row->{$countColumn})
            ->addColumn('action', fn ($row) => ActionButtons::make(
                urlView: url($this->routePrefix().'/listdata/'.rawurlencode($row->email)),
            ))
            ->rawColumns(['action'])
            ->make(true);
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

    protected function authorizeStudent(Request $request, ?string $email): void
    {
        abort_unless(Mahasiswa::canBeViewedBy($request->user(), $email), 404);
    }
}
