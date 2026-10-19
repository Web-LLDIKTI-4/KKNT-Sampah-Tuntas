<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\Mahasiswa;
use App\Support\ActionButtons;
use App\Support\XlsxStreamer;
use Carbon\Carbon;
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
        $student = Mahasiswa::with('sp')->where('email', $email)->first();
        $time = fn ($value) => $value ? Carbon::parse($value)->format('H:i:s') : null;
        $rows = Kehadiran::where('email', $email)->without(['mahasiswa', 'dplmentoring'])->orderBy('tanggal')->orderBy('id_kehadiran')->lazy(1000);

        return (new XlsxStreamer('Log Kehadiran Mahasiswa', [
            XlsxStreamer::column('NIM', fn () => $student?->nim, 15),
            XlsxStreamer::column('Nama Mahasiswa', fn () => $student?->nama, 30),
            XlsxStreamer::column('Nama Perguruan Tinggi', fn () => $student?->sp?->nm_lemb, 35),
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Status Kehadiran', fn (Kehadiran $row) => ucfirst((string) $row->status_kehadiran), 18),
            XlsxStreamer::column('Jam Masuk', fn (Kehadiran $row) => $time($row->waktu_masuk), 12),
            XlsxStreamer::column('Jam Pulang', fn (Kehadiran $row) => $time($row->waktu_pulang), 12),
        ]))
            ->info(['Mahasiswa' => $student?->nama, 'Email' => $email])
            ->download('kehadiran_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx', $rows);
    }
}
