<?php

namespace App\Http\Controllers;

use App\Exports\LogHarianByMhsExport;
use App\Models\Logkegiatan;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Facades\DataTables;

class AdmlogkegiatanController extends StudentLogReportController
{
    protected function viewPrefix(): string
    {
        return 'logkegiatan.dpl';
    }

    protected function routePrefix(): string
    {
        return 'admlogkegiatan';
    }

    protected function logRelation(): string
    {
        return 'logkegiatan';
    }

    protected function detailTable(string $email): DataTableAbstract
    {
        // PT hanya melihat tautan, tidak isi deskripsi
        $showDeskripsi = in_array(auth()->user()->role, ['admin', 'dpl'], true);

        return DataTables::of(Logkegiatan::where('email', $email)->with('kpi')->orderBy('tanggal')->get())
            ->addIndexColumn()
            ->editColumn('tanggal', fn ($row) => Carbon::parse($row->tanggal)->format('d-m-Y'))
            ->addColumn('nama_kpi', fn ($row) => $row->kpi->nama_kpi ?? '')
            ->editColumn('deskripsi', fn ($row) => ($showDeskripsi ? HtmlSanitizer::clean($row->deskripsi) : 'tidak ditampilkan <br />')
                .' '.HtmlSanitizer::link($row->tautan))
            ->rawColumns(['deskripsi']);
    }

    protected function exportFor(string $email)
    {
        return Excel::download(new LogHarianByMhsExport($email), 'logharian_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
