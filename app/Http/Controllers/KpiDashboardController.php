<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiDashboardRequest;
use App\Services\KpiRekapService;
use App\Services\KpiSampahService;

class KpiDashboardController extends Controller
{
    public function index(KpiDashboardRequest $request, KpiRekapService $rekap, KpiSampahService $sampah)
    {
        $filter = $this->filter($request);
        $laporan = $sampah->drilldownPublik($filter);

        // Navigasi laporan berjenjang (juga dari dashboard home) memuat ulang bagian laporan saja
        if ($request->ajax()) {
            return view('laporan._capaian_publik', $laporan);
        }

        // Rekap LLDIKTI ikut bulan laporan (default bulan terakhir yang ada log)
        $filterRekap = ['bulan' => $laporan['params']['bulan'], 'kodept' => $filter['kodept']];

        return view('kpidashboard.index', [
            'isPt' => $request->user()->role === 'pt',
            'summary' => $rekap->summary(['kodept' => $filter['kodept']]),
            'laporan' => $laporan,
            'rekap' => $sampah->rekapLldikti($filterRekap),
            'total' => $sampah->total($filterRekap),
        ]);
    }

    // PT dikunci ke PT-nya sendiri, filter kodept dari input diabaikan
    private function filter(KpiDashboardRequest $request): array
    {
        $user = $request->user();

        return [
            'kodept' => $user->role === 'pt' ? $user->email : $request->validated('kodept'),
            'bulan' => $request->validated('bulan'),
            'id_kecamatan' => $request->validated('kecamatan'),
            'id_desa' => $request->validated('desa'),
            'klaster' => $request->validated('klaster'),
        ];
    }
}
