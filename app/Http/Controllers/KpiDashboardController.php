<?php

namespace App\Http\Controllers;

use App\Exports\KpiRekapExport;
use App\Http\Requests\KpiDashboardRequest;
use App\Models\Kpitarget;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Services\KpiRekapService;
use Maatwebsite\Excel\Facades\Excel;

class KpiDashboardController extends Controller
{
    public function index(KpiDashboardRequest $request, KpiRekapService $rekap)
    {
        $isPt = $request->user()->role === 'pt';
        $filter = $this->filter($request);

        // Filter di halaman memuat ulang bagian isi saja lewat AJAX
        return view($request->ajax() ? 'kpidashboard._content' : 'kpidashboard.index', [
            'isPt' => $isPt,
            'filter' => $filter,
            'summary' => $rekap->summary($filter),
            'rekapPerKpi' => $rekap->rekapPerKpi($filter),
            'rekapPerDaerah' => $isPt ? collect() : $rekap->rekapPerDaerah($filter),
            'rekapPerPt' => $rekap->rekapPerPt($filter),
            'isian' => $filter['kodept'] ? $rekap->isianKelompok($filter) : collect(),
            'lokasiList' => LokasiProgram::orderBy('nama_lokasi')->get(['id', 'nama_lokasi']),
            'ptList' => $isPt ? collect() : Satuanpendidikan::whereIn('npsn', Mahasiswa::whereNotNull('kodept')->select('kodept'))
                ->orderBy('nm_lemb')->get(['npsn', 'nm_lemb']),
            'kegiatanList' => Kpitarget::with('kpi:id_kpi,nama_kpi')->orderBy('kegiatan')->get(),
        ]);
    }

    public function export(KpiDashboardRequest $request, KpiRekapService $rekap)
    {
        return Excel::download(new KpiRekapExport($rekap, $this->filter($request)), 'rekap_kpi_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    // PT dikunci ke PT-nya sendiri, filter kodept dari input diabaikan
    private function filter(KpiDashboardRequest $request): array
    {
        $user = $request->user();

        return [
            'lokasi' => $request->validated('lokasi'),
            'kodept' => $user->role === 'pt' ? $user->email : $request->validated('kodept'),
            'id_target' => $request->validated('id_target'),
        ];
    }
}
