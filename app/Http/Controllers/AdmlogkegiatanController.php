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
        // Eloquent: sort tanggal kronologis di SQL
        return DataTables::eloquent(Logkegiatan::where('email', $email)->whereNotNull('deskripsi')->with('kategoriKegiatan')->without(['mahasiswa', 'dplmentoring'])->unless($this->userSorts(), fn ($q) => $q->orderBy('tanggal')))
            ->addIndexColumn()
            ->editColumn('tanggal', fn ($row) => Carbon::parse($row->tanggal)->format('d-m-Y'))
            ->filterColumn('tanggal', fn ($q, $k) => $this->filterTanggal($q, $k))
            ->addColumn('nama_kategori', fn ($row) => $row->kategoriKegiatan->nama_kategori ?? '')
            ->filterColumn('nama_kategori', fn ($q, $k) => $q->whereHas('kategoriKegiatan', fn ($kategori) => $kategori->where('nama_kategori', 'like', "%{$k}%")))
            ->editColumn('deskripsi', fn ($row) => HtmlSanitizer::clean($row->deskripsi))
            ->addColumn('tautan', fn ($row) => HtmlSanitizer::link($row->tautan, 'Lihat bukti'))
            ->rawColumns(['deskripsi', 'tautan']);
    }

    protected function exportFor(string $email)
    {
        return Excel::download(new LogHarianByMhsExport($email), 'log_aktivitas_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
