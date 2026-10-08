<?php

namespace App\Http\Controllers;

use App\Exports\LogKehadiranByMhsExport;
use App\Models\Kehadiran;
use App\Support\ActionButtons;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Facades\DataTables;

class AdmlogkehadiranController extends StudentLogReportController
{
    protected function viewPrefix(): string
    {
        return 'logkehadiran';
    }

    protected function routePrefix(): string
    {
        return 'admlogkehadiran';
    }

    protected function logRelation(): string
    {
        return 'logkehadiran';
    }

    // Tabel kehadiran tidak punya kolom deskripsi: hitung semua baris
    protected function constrainCountedLogs($query): void {}

    protected function detailTable(string $email): DataTableAbstract
    {
        return DataTables::of(Kehadiran::where('email', $email)->with('mahasiswa.sp')->orderByDesc('tanggal')->get())
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => $row->mahasiswa->nim ?? 'NIM tidak tersedia')
            ->addColumn('nama_mahasiswa', fn ($row) => $row->mahasiswa->nama ?? 'Nama tidak tersedia')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? 'Perguruan Tinggi tidak tersedia')
            ->editColumn('status_kehadiran', fn ($row) => LogkehadiranController::badge($row->status_kehadiran))
            ->editColumn('tanggal', fn ($row) => $row->tanggal ? date('d-m-Y', strtotime($row->tanggal)) : '-')
            ->editColumn('waktu_masuk', fn ($row) => $row->waktu_masuk ? date('H:i:s', strtotime($row->waktu_masuk)).' WIB' : '-')
            ->editColumn('waktu_pulang', fn ($row) => $row->waktu_pulang ? date('H:i:s', strtotime($row->waktu_pulang)).' WIB' : '-')
            ->addColumn('coordinates_datang', fn ($row) => ActionButtons::map($row->latitude_datang, $row->longitude_datang))
            ->addColumn('coordinates_pulang', fn ($row) => ActionButtons::map($row->latitude_pulang, $row->longitude_pulang))
            ->rawColumns(['status_kehadiran', 'coordinates_datang', 'coordinates_pulang']);
    }

    protected function exportFor(string $email)
    {
        return Excel::download(new LogKehadiranByMhsExport($email), 'kehadiran_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
