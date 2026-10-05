<?php

namespace App\Http\Controllers;

use App\Exports\LogHarianByMhsExport;
use App\Models\Logkegiatan;
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
        $showIdentitas = $this->showIdentitas();

        return DataTables::of(Logkegiatan::where('email', $email)->without(['mahasiswa', 'dplmentoring'])->orderBy('tanggal')->get())
            ->addIndexColumn()
            ->editColumn('tanggal', fn ($row) => Carbon::parse($row->tanggal)->format('d-m-Y'))
            ->editColumn('nama_kepala_keluarga', fn ($row) => $showIdentitas ? $row->nama_kepala_keluarga : 'tidak ditampilkan')
            ->editColumn('alamat_rumah', fn ($row) => $showIdentitas ? $row->alamat_rumah : 'tidak ditampilkan');
    }

    protected function exportFor(string $email)
    {
        return Excel::download(new LogHarianByMhsExport($email, $this->showIdentitas()), 'logharian_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    // Selain admin/dpl: identitas rumah tangga disembunyikan (tabel & export)
    private function showIdentitas(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'dpl'], true);
    }
}
