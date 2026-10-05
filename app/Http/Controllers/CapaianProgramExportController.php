<?php

namespace App\Http\Controllers;

use App\Exports\Sheets\CapaianProgramSheet;
use App\Http\Requests\Auth\LaporanPublikRequest;
use App\Http\Requests\KpiDashboardRequest;
use App\Models\Kpisampah;
use App\Services\KpiSampahService;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

// Export "Capaian Program": dashboard admin (__invoke) & halaman login (publik); isi hanya agregat persen/klaster
class CapaianProgramExportController extends Controller
{
    public function __invoke(KpiDashboardRequest $request, KpiSampahService $sampah)
    {
        return $this->unduh($sampah->capaianProgram([
            'bulan' => $request->validated('bulan'),
            'klaster' => $request->validated('klaster'),
        ]));
    }

    // Publik: di-cache per versi data (direset hook Kpisampah/Kpicapaian) agar unduhan berulang tidak query ulang
    public function publik(LaporanPublikRequest $request, KpiSampahService $sampah)
    {
        $filter = ['bulan' => $request->validated('bulan'), 'klaster' => $request->validated('klaster')];
        $versi = Cache::get(Kpisampah::PUBLIC_VERSION_CACHE_KEY, '0');

        return $this->unduh(Cache::remember('login.capaian-program.'.$versi.'.'.md5(json_encode($filter)), now()->addMinutes(10),
            fn () => $sampah->capaianProgram($filter)));
    }

    private function unduh(array $data)
    {
        return Excel::download(new CapaianProgramSheet($data), 'capaian-program-'.($data['bulan'] ?? 'semua').'.xlsx');
    }
}
