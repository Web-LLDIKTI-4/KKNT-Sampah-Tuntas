<?php

namespace App\Http\Controllers;

use App\Exports\Sheets\RekapLldiktiSheet;
use App\Http\Requests\RekapSampahRequest;
use App\Models\Kecamatan;
use App\Models\Mahasiswa;
use App\Models\PenguranganSampah;
use App\Models\Satuanpendidikan;
use App\Services\PenguranganSampahService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class RekapsampahController extends Controller
{
    public function index(RekapSampahRequest $request, PenguranganSampahService $sampah)
    {
        $isPt = $request->user()->role === 'pt';
        $data = $this->data($request, $sampah);

        // Filter di tabel memuat ulang bagian tabel saja (juga dipakai di dashboard home)
        return view($request->ajax() ? 'rekapsampah._tabel' : 'rekapsampah.index', $data + [
            'isPt' => $isPt,
            'kecamatanList' => Kecamatan::orderBy('kecamatan')->get(['id_kecamatan', 'kecamatan']),
            'ptList' => $isPt ? collect() : Cache::remember('rekapsampah.ptList', now()->addMinutes(10), fn () => Satuanpendidikan::whereIn('npsn', Mahasiswa::whereNotNull('kodept')->select('kodept'))
                ->orderBy('nm_lemb')->toBase()->get(['npsn', 'nm_lemb'])),
        ]);
    }

    // Format LLDIKTI (per kelurahan), mengikuti filter & klaster aktif
    public function export(RekapSampahRequest $request, PenguranganSampahService $sampah)
    {
        $data = $this->data($request, $sampah);

        return Excel::download(
            new RekapLldiktiSheet($data['rekap']),
            'rekap_sampah_'.($data['filter']['klaster'] ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx'
        );
    }

    // Di-cache per filter + scope user (PT hanya datanya sendiri); versi berganti saat data sampah berubah
    private function data(RekapSampahRequest $request, PenguranganSampahService $sampah): array
    {
        $user = $request->user();
        $isPt = $user->role === 'pt';
        $input = $request->safe()->only($isPt ? ['bulan', 'kecamatan'] : ['bulan', 'kecamatan', 'kodept', 'klaster']);
        $scope = $isPt ? 'pt:'.$user->email : 'all';
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0');

        return Cache::remember('rekapsampah.v1.'.$versi.'.'.md5($scope.'|'.json_encode($input)), now()->addMinutes(10), function () use ($request, $sampah, $isPt) {
            [$filter, $klasterPt] = $this->filter($request, $sampah);

            return [
                'filter' => $filter,
                'klasterPt' => $klasterPt,
                'total' => $sampah->total($filter),
                'rekap' => $sampah->rekapLldikti($filter),
                'bulanList' => $sampah->bulanList($isPt ? $filter['kodept'] : null),
            ];
        });
    }

    /**
     * @return array{0: array, 1: Collection} filter query & klaster tiap PT (kosong untuk role PT)
     */
    private function filter(RekapSampahRequest $request, PenguranganSampahService $sampah): array
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
