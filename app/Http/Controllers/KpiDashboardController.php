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

        // PT dikunci ke PT-nya sendiri, filter kodept dari input diabaikan
        $filter = [
            'lokasi' => $request->validated('lokasi'),
            'kodept' => $isPt ? $user->email : $request->validated('kodept'),
            'id_target' => $request->validated('id_target'),
        ];

        // Filter di halaman memuat ulang bagian isi saja lewat AJAX
        return view($request->ajax() ? 'kpidashboard._content' : 'kpidashboard.index', [
            'isPt' => $isPt,
            'filter' => $filter,
            'summary' => $rekap->summary($filter),
            'rekapPerPt' => $rekap->rekapPerPt($filter),
            'isian' => $filter['kodept'] ? $rekap->isianKelompok($filter) : collect(),
            'lokasiList' => LokasiProgram::orderBy('nama_lokasi')->get(['id', 'nama_lokasi']),
            'ptList' => $isPt ? collect() : Satuanpendidikan::whereIn('npsn', Mahasiswa::whereNotNull('kodept')->select('kodept'))
                ->orderBy('nm_lemb')->get(['npsn', 'nm_lemb']),
            'kegiatanList' => Kpitarget::with('kpi:id_kpi,nama_kpi')->orderBy('kegiatan')->get(),
        ]);
    }
}
