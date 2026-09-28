<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiDashboardRequest;
use App\Models\Kpitarget;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Services\KpiRekapService;

class KpiDashboardController extends Controller
{
    public function index(KpiDashboardRequest $request, KpiRekapService $rekap)
    {
        $user = $request->user();
        $isPt = $user->role === 'pt';

        // PT dikunci ke PT & lokasinya sendiri, filter dari input diabaikan
        $filter = [
            'lokasi' => $isPt ? $user->location_program : $request->validated('lokasi'),
            'kodept' => $isPt ? $user->email : $request->validated('kodept'),
            'id_target' => $request->validated('id_target'),
        ];

        $rekapPerPt = $rekap->rekapPerPt($filter);

        return view('kpidashboard.index', [
            'isPt' => $isPt,
            'filter' => $filter,
            'summary' => $rekap->summary($filter, $rekapPerPt),
            'rekapPerPt' => $rekapPerPt,
            'isian' => $filter['kodept'] ? $rekap->isianKelompok($filter) : collect(),
            'lokasiList' => LokasiProgram::orderBy('nama_lokasi')->get(['id', 'nama_lokasi']),
            'ptList' => $isPt ? collect() : Satuanpendidikan::whereIn('npsn', Mahasiswa::whereNotNull('kodept')->select('kodept'))
                ->orderBy('nm_lemb')->get(['npsn', 'nm_lemb']),
            'kegiatanList' => Kpitarget::with('kpi:id_kpi,nama_kpi')->orderBy('tahapan')->get(),
        ]);
    }
}
