<?php

namespace App\Http\Controllers;

use App\Exports\LogHarianByMhsExport;
use App\Models\Logkegiatan;
use App\Support\DataTableOrder;
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
        $showDeskripsi = $this->showDeskripsi();

        $query = Logkegiatan::where('logkegiatan.email', $email)
            ->without(['kpi', 'mahasiswa', 'dplmentoring'])
            ->select('logkegiatan.id_log', 'logkegiatan.tanggal', 'logkegiatan.tautan',
                'logkegiatan.volume', 'logkegiatan.satuan', 'kpi.nama_kpi')
            ->when($showDeskripsi, fn ($q) => $q->addSelect('logkegiatan.deskripsi'))
            ->leftJoin('kpi', 'kpi.id_kpi', '=', 'logkegiatan.id_kpi')
            ->when(! DataTableOrder::requested(), fn ($q) => $q->orderBy('logkegiatan.tanggal'));

        $table = DataTables::eloquent($query);
        if (! $showDeskripsi) {
            // Cegah PT menebak isi deskripsi lewat pencarian/urutan; pakai tautan saja
            $table->filterColumn('deskripsi', fn ($q, $keyword) => $q->where('logkegiatan.tautan', 'like', "%{$keyword}%"))
                ->orderColumn('deskripsi', 'logkegiatan.tautan $1');
        }

        return $table
            ->addIndexColumn()
            ->editColumn('tanggal', fn ($row) => Carbon::parse($row->tanggal)->format('d-m-Y'))
            ->editColumn('nama_kpi', fn ($row) => $row->nama_kpi ?? '')
            ->filterColumn('nama_kpi', fn ($q, $keyword) => $q->where('kpi.nama_kpi', 'like', "%{$keyword}%"))
            ->orderColumn('nama_kpi', 'kpi.nama_kpi $1')
            ->editColumn('deskripsi', fn ($row) => ($showDeskripsi ? HtmlSanitizer::clean($row->deskripsi) : 'tidak ditampilkan <br />')
                .' '.HtmlSanitizer::link($row->tautan))
            // Hanya kolom yang ditampilkan yang boleh dicari/diurutkan (cegah columns[name] arbitrer)
            ->whitelist(['tanggal', 'deskripsi', 'volume', 'satuan', 'nama_kpi'])
            ->rawColumns(['deskripsi']);
    }

    // PT hanya melihat tautan, tidak isi deskripsi (tabel & export)
    private function showDeskripsi(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'dpl'], true);
    }

    protected function exportFor(string $email)
    {
        return Excel::download(new LogHarianByMhsExport($email, $this->showDeskripsi()), 'logharian_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
