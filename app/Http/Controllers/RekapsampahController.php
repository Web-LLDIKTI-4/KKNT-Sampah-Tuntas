<?php

namespace App\Http\Controllers;

use App\Exports\Sheets\RekapLldiktiSheet;
use App\Http\Requests\RekapSampahRequest;
use App\Models\Kecamatan;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Services\KpiSampahService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class RekapsampahController extends Controller
{
    public function index(RekapSampahRequest $request, KpiSampahService $sampah)
    {
        $isPt = $request->user()->role === 'pt';
        [$filter, $klasterPt] = $this->filter($request, $sampah);

        // Filter di tabel memuat ulang bagian tabel saja (juga dipakai di dashboard home)
        return view($request->ajax() ? 'rekapsampah._tabel' : 'rekapsampah.index', [
            'isPt' => $isPt,
            'filter' => $filter,
            'klasterPt' => $klasterPt,
            'total' => $sampah->total($filter),
            'rekap' => $sampah->rekapLldikti($filter),
            'bulanList' => $sampah->bulanList($isPt ? $filter['kodept'] : null),
            'kecamatanList' => Kecamatan::orderBy('kecamatan')->get(['id_kecamatan', 'kecamatan']),
            'ptList' => $isPt ? collect() : Satuanpendidikan::whereIn('npsn', Mahasiswa::whereNotNull('kodept')->select('kodept'))
                ->orderBy('nm_lemb')->get(['npsn', 'nm_lemb']),
        ]);
    }

    // Format LLDIKTI (per kelurahan), mengikuti filter & klaster aktif
    public function export(RekapSampahRequest $request, KpiSampahService $sampah)
    {
        [$filter] = $this->filter($request, $sampah);

        return Excel::download(
            new RekapLldiktiSheet($sampah->rekapLldikti($filter)),
            'rekap_sampah_'.($filter['klaster'] ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx'
        );
    }

    /**
     * @return array{0: array, 1: Collection} filter query & klaster tiap PT (kosong untuk role PT)
     */
    private function filter(RekapSampahRequest $request, KpiSampahService $sampah): array
    {
        $user = $request->user();
        $isPt = $user->role === 'pt';
        $kodept = $isPt ? $user->email : $request->validated('kodept');

        // PT dikunci ke PT-nya sendiri; bulan default = bulan terakhir yang ada datanya, "semua" = tanpa filter bulan
        $bulan = $request->validated('bulan');
        $bulan = $bulan === 'semua' ? null : ($bulan ?: $sampah->bulanTerakhir($kodept));
        $filter = ['bulan' => $bulan, 'id_kecamatan' => $request->validated('kecamatan'), 'kodept' => $kodept];

        if ($isPt) {
            return [$filter + ['klaster' => null], collect()];
        }

        // Klaster dihitung sebelum dipilih agar jumlah PT tiap klaster tetap tampil
        $klasterPt = $sampah->klasterPt($filter);
        $filter['klaster'] = $request->validated('klaster');
        if ($filter['klaster']) {
            $filter['kodept_in'] = $klasterPt->where('klaster', $filter['klaster'])->keys()->all();
        }

        return [$filter, $klasterPt];
    }
}
