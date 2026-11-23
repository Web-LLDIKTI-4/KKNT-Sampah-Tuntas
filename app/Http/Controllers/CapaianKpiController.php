<?php

namespace App\Http\Controllers;

use App\Exports\CapaianKpiKelurahanExport;
use App\Http\Requests\CapaianKpiDetailRequest;
use App\Http\Requests\CapaianKpiRequest;
use App\Models\PenguranganSampah;
use App\Services\PenguranganSampahService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CapaianKpiController extends Controller
{
    private const PER_PAGE = 25;

    public function index(CapaianKpiRequest $request, PenguranganSampahService $sampah)
    {
        $data = $this->data($request, $sampah);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $data['kelurahan'] = new LengthAwarePaginator(
            $data['kelurahan']->forPage($page, self::PER_PAGE)->values(),
            $data['kelurahan']->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        // Filter, cari, sort & halaman memuat ulang bagian tabel saja (drilldown.js)
        return view($request->ajax() ? 'capaiankpi._tabel' : 'capaiankpi.index', $data);
    }

    public function export(CapaianKpiRequest $request, PenguranganSampahService $sampah)
    {
        $data = $this->data($request, $sampah);
        $params = $data['params'];

        return (new CapaianKpiKelurahanExport($data['kelurahan'], [
            'Bulan' => self::labelBulan($data['bulan']),
            'Pencarian' => $params['q'],
            'Urutan' => $params['sort'] ? CapaianKpiRequest::SORT[$params['sort']].' ('.($params['dir'] === 'desc' ? 'Z-A' : 'A-Z').')' : null,
        ]))->download('capaian-kpi-kelurahan-'.($data['bulan'] ?? 'kosong').'_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    // Isi modal detail capaian PTS di satu kelurahan (dimuat ke #modalku)
    public function detail(CapaianKpiDetailRequest $request, PenguranganSampahService $sampah)
    {
        $bulan = $request->validated('bulan');

        return view('capaiankpi._detail', [
            'detail' => $sampah->detailPtKelurahan($request->validated('desa'), $request->validated('kodept'), $bulan),
            'semuaBulan' => $bulan === PenguranganSampahService::SEMUA_BULAN,
        ]);
    }

    private static function labelBulan(?string $bulan): string
    {
        return match (true) {
            $bulan === PenguranganSampahService::SEMUA_BULAN => 'Semua Bulan',
            (bool) $bulan => Carbon::createFromFormat('!Y-m', $bulan)->translatedFormat('F Y'),
            default => 'Belum ada data',
        };
    }

    // Data per bulan di-cache (versi berganti saat data sampah berubah); cari & sort diterapkan setelahnya
    private function data(CapaianKpiRequest $request, PenguranganSampahService $sampah): array
    {
        $bulan = $request->validated('bulan');
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0');
        $data = Cache::remember('capaiankpi.v2.'.$versi.'.'.($bulan ?? 'terakhir'), now()->addMinutes(10), fn () => $sampah->capaianKpiKelurahan($bulan));

        $params = [
            'q' => $request->validated('q'),
            'sort' => $request->validated('sort'),
            'dir' => $request->validated('dir') ?? 'asc',
        ];
        $data['kelurahan'] = $sampah->saringKpiKelurahan($data['kelurahan'], $params['q'], $params['sort'], $params['dir']);

        return $data + ['params' => $params];
    }
}
