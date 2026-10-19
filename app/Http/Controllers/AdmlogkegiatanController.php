<?php

namespace App\Http\Controllers;

use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Support\HtmlSanitizer;
use App\Support\XlsxStreamer;
use Illuminate\Support\Carbon;
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
            ->filterColumn('nama_kategori', fn ($q, $k) => $q->whereHas('kategoriKegiatan', fn ($kategori) => $kategori->where('nama_kategori', 'like', $this->likeContains($k))))
            ->editColumn('deskripsi', fn ($row) => HtmlSanitizer::clean($row->deskripsi))
            ->addColumn('tautan', fn ($row) => HtmlSanitizer::link($row->tautan, 'Lihat bukti'))
            ->rawColumns(['deskripsi', 'tautan']);
    }

    // Same query as the detail table (search/order follow the table when sent)
    protected function exportFor(string $email)
    {
        $this->sanitizeDataTablesInput();
        $rows = $this->dataTablesExportQuery($this->detailTable($email), 'logkegiatan.id_log')->lazy(1000);

        return (new XlsxStreamer('Log Aktivitas Mahasiswa', [
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Kategori Kegiatan', fn (Logkegiatan $row) => $row->kategoriKegiatan?->nama_kategori, 25),
            XlsxStreamer::column('Nama Aktivitas', 'nama_aktivitas', 30),
            XlsxStreamer::column('Deskripsi Kegiatan', fn (Logkegiatan $row) => strip_tags((string) $row->deskripsi), 60),
            XlsxStreamer::column('Volume/Kuantitas Output', 'volume', 14),
            XlsxStreamer::column('Satuan', 'satuan', 12),
            XlsxStreamer::column('Tautan Bukti', 'tautan', 40),
        ]))
            ->info([
                'Mahasiswa' => Mahasiswa::where('email', $email)->value('nama'),
                'Email' => $email,
                'Kata kunci' => request('search.value'),
            ])
            ->download('log_aktivitas_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx', $rows);
    }
}
