<?php

namespace App\Http\Controllers;

use App\Exports\Sheets\CapaianProgramSheet;
use App\Http\Requests\Auth\LaporanPublikRequest;
use App\Http\Requests\PenguranganSampahDashboardRequest;
use App\Models\PenguranganSampah;
use App\Services\PenguranganSampahService;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

// Export "Capaian Program": dashboard admin (__invoke) & halaman login (publik); isi hanya agregat persen/klaster
class CapaianProgramExportController extends Controller
{
    public function __invoke(PenguranganSampahDashboardRequest $request, PenguranganSampahService $sampah)
    {
        return $this->unduh($sampah->capaianProgram([
            'bulan' => $request->validated('bulan'),
            'klaster' => $request->validated('klaster'),
        ]));
    }

    // Publik: di-cache per versi data (direset hook PenguranganSampah/CapaianKegiatan) agar unduhan berulang tidak query ulang
    public function publik(LaporanPublikRequest $request, PenguranganSampahService $sampah)
    {
        $filter = ['bulan' => $request->validated('bulan'), 'klaster' => $request->validated('klaster')];
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0');

        return $this->unduh(Cache::remember('login.capaian-program.v2.'.$versi.'.'.md5(json_encode($filter)), now()->addMinutes(10),
            fn () => $sampah->capaianProgram($filter)));
    }

    private function unduh(array $data)
    {
        return Excel::download(new CapaianProgramSheet($data), 'capaian-program-'.($data['bulan'] ?? 'semua').'.xlsx');
    }
}
