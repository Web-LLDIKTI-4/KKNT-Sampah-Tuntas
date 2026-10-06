<?php

namespace App\Http\Controllers;

use App\Exports\CapaiankpiExport;
use App\Models\Desa;
use App\Models\Kpicapaian;
use App\Services\KpiSampahService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KpicapaianController extends Controller
{
    // Read-only: rekap sampah desa mahasiswa dari log harian (semua bulan)
    public function index(Request $request, KpiSampahService $sampah)
    {
        $idDesa = $sampah->desaMahasiswa($request->user()->email);
        $filter = ['id_desa' => $idDesa];

        return view('kpicapaian.index', [
            'desa' => $idDesa ? Desa::with('kecamatan')->find($idDesa) : null,
            'rekap' => $idDesa ? $sampah->rekapLldikti($filter) : collect(),
            'total' => $idDesa ? $sampah->total($filter) : null,
        ]);
    }

    public function listdata()
    {
        return view('kpicapaian.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Kpicapaian::ownedBy($request->user())
            ->with(['kpi', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
            ->orderByDesc('bulan')->orderByDesc('created_at')
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('lokasi', function (Kpicapaian $row) {
                $lokasi = $row->pjdesa?->mahasiswa?->user?->locationProgram?->nama_lokasi;
                $desa = $row->pjdesa?->desa;
                if (! $lokasi || ! $desa?->kecamatan) {
                    return 'Tidak Diketahui';
                }

                return e($lokasi).'<br /> '.e($desa->kecamatan->kecamatan).', '.e($desa->desa);
            })
            ->addColumn('nama_kpi', fn (Kpicapaian $row) => $row->kpi->nama_kpi ?? '')
            // Label tampilan; kolom 'bulan' mentah (Y-m-d) tetap dikirim untuk order
            ->addColumn('bulan_label', fn (Kpicapaian $row) => $row->bulan ? Carbon::parse($row->bulan)->translatedFormat('F Y') : '-')
            ->editColumn('permasalahan', fn (Kpicapaian $row) => nl2br(e($row->permasalahan)))
            ->editColumn('solusi', fn (Kpicapaian $row) => nl2br(e($row->solusi)))
            ->editColumn('kendala', fn (Kpicapaian $row) => nl2br(e($row->kendala)))
            ->editColumn('status_capaian', fn (Kpicapaian $row) => Kpicapaian::statusBadge($row->status_capaian))
            ->editColumn('tautan', fn (Kpicapaian $row) => HtmlSanitizer::link($row->tautan))
            ->rawColumns(['lokasi', 'tautan', 'status_capaian', 'permasalahan', 'solusi', 'kendala'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $email = $user->role === 'mahasiswa' ? $user->email : null;

        return Excel::download(new CapaiankpiExport($email), 'capaian_kpi_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
