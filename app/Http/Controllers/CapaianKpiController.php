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
    public function index(CapaianKpiRequest $request, PenguranganSampahService $sampah)
    {
        $data = $this->data($request, $sampah);
        $total = $data['kelurahan']->count();
        $pilihan = (int) ($request->validated('per_page') ?? CapaianKpiRequest::PER_PAGE_DEFAULT);
        // 0 = semua kelurahan dalam satu halaman
        $perPage = $pilihan ?: max($total, 1);
        $page = $pilihan ? LengthAwarePaginator::resolveCurrentPage() : 1;
        $data['params']['per_page'] = $pilihan;
        $data['kelurahan'] = new LengthAwarePaginator(
            $data['kelurahan']->forPage($page, $perPage)->values(),
            $total,
            $perPage,
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
            'semuaBulan' => ! $bulan || $bulan === PenguranganSampahService::SEMUA_BULAN,
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
        $bulan = $bulan ?: PenguranganSampahService::SEMUA_BULAN;
        $data = Cache::remember('capaiankpi.v4.'.$versi.'.'.$bulan, now()->addMinutes(10), fn () => $sampah->capaianKpiKelurahan($bulan));

        $params = [
            'q' => $request->validated('q'),
            'sort' => $request->validated('sort'),
            'dir' => $request->validated('dir') ?? 'asc',
        ];
        $data['kelurahan'] = $sampah->saringKpiKelurahan($data['kelurahan'], $params['q'], $params['sort'], $params['dir']);

        return $data + ['params' => $params];
    }
}
