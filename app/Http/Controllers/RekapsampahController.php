<?php

namespace App\Http\Controllers;

use App\Exports\DataSampahExport;
use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Requests\RekapSampahRequest;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\PenguranganSampah;
use App\Models\Satuanpendidikan;
use App\Services\PenguranganSampahService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use WeakMap;
use Yajra\DataTables\Facades\DataTables;

class RekapsampahController extends Controller
{
    use HandlesDataTablesOrder;

    public const LOKASI_LIST_CACHE_KEY = 'rekapsampah.lokasiList';

    // Kolom hitung barisLog() pada response listdataserver
    private const KOLOM_HITUNG = [
        'jml_rumah', 'jml_rumah_memilah', 'persen_ketaatan', 'organik', 'anorganik', 'residu', 'jml_jiwa',
        'total_terkelola', 'total_dihasilkan', 'persen_pengelolaan', 'persen_penurunan_timbulan',
    ];

    public function index(RekapSampahRequest $request, PenguranganSampahService $sampah)
    {
        $data = $this->dataUser($request, $sampah);

        // Filter di tabel memuat ulang bagian tabel saja
        return view($request->ajax() ? 'rekapsampah._tabel' : 'rekapsampah.index', $this->viewData($data, $request->user()->role === 'pt'));
    }

    // Landing publik: total per lokasi program, filter bulan saja (default semua bulan); tanpa data per rumah (nama KK/alamat)
    public function publik(Request $request, PenguranganSampahService $sampah)
    {
        // Hanya bulan yang ada datanya, agar kombinasi key cache terbatas
        $bulanList = Cache::remember(PenguranganSampah::PUBLIC_BULAN_CACHE_KEY, now()->addMinutes(10), fn () => $sampah->bulanList()->all());
        $bulan = $request->validate(['bulan' => ['nullable', 'string', Rule::in(['semua', ...$bulanList])]])['bulan'] ?? 'semua';

        return view('rekapsampah._data_sampah_publik', [
            'subtotal' => $this->data(['bulan' => $bulan], $sampah, null, false)['subtotal'],
            'bulanList' => $bulanList,
            'bulan' => $bulan,
        ]);
    }

    private function viewData(array $data, bool $isPt): array
    {
        return $data + [
            'isPt' => $isPt,
            'kecamatanList' => Kecamatan::orderBy('kecamatan')->get(['id_kecamatan', 'kecamatan']),
            'ptList' => $isPt ? collect() : Cache::remember('rekapsampah.ptList', now()->addMinutes(10), fn () => Satuanpendidikan::whereIn('npsn', Mahasiswa::whereNotNull('kodept')->select('kodept'))
                ->orderBy('nm_lemb')->toBase()->get(['npsn', 'nm_lemb'])),
            'kolomDataSampah' => DataSampahExport::kolom($data['mingguan']),
            'lokasiList' => Cache::remember(self::LOKASI_LIST_CACHE_KEY, now()->addMinutes(10), fn () => LokasiProgram::orderBy('nama_lokasi')->toBase()->get(['id', 'nama_lokasi'])),
        ];
    }

    // Detail Data Sampah per log (server-side); scope PT dari filter() yang sama dengan index/export
    public function listdataserver(RekapSampahRequest $request, PenguranganSampahService $sampah)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();
        $filter = $this->dataUser($request, $sampah)['filter'];
        $keyword = trim((string) DataTables::getRequest()->keyword());
        $memo = new WeakMap;
        $hitung = fn (object $r) => $memo[$r] ??= PenguranganSampahService::barisLog($r, $r->koefisien === null ? null : (float) $r->koefisien);

        $table = DataTables::query($sampah->dataSampahQuery($filter))
            ->filter(function (Builder $q) use ($keyword) {
                if ($keyword === '') {
                    return;
                }
                $like = $this->likeContains($keyword);
                $q->where(fn (Builder $w) => $w->where('s.nama_kepala_keluarga', 'like', $like)
                    ->orWhere('s.alamat_rumah', 'like', $like)
                    ->orWhere('d.desa', 'like', $like));
            })
            // Urutan dikunci ke urutan grup di dataSampahQuery()
            ->order(fn () => null)
            ->editColumn('nama_lokasi', fn ($r) => $r->nama_lokasi ?? 'Tanpa Lokasi Program')
            ->editColumn('koefisien', fn ($r) => $r->koefisien === null ? null : (float) $r->koefisien)
            ->editColumn('bulan', fn ($r) => substr($r->bulan, 0, 7))
            ->editColumn('minggu', fn ($r) => (int) $r->minggu)
            ->editColumn('tanggal', fn ($r) => Carbon::parse($r->tanggal)->format('d-m-Y'))
            ->addColumn('nama_bulan', fn ($r) => Carbon::parse($r->bulan)->translatedFormat('F Y'))
            ->addColumn('minggu_label', fn ($r) => PenguranganSampahService::labelMinggu((int) $r->minggu, $r->bulan));
        foreach (self::KOLOM_HITUNG as $key) {
            $table->addColumn($key, fn ($r) => $hitung($r)[$key]);
        }

        // Klien wajib escape (render.text/textContent); tanpa kolom lain (email dsb.)
        return $table->escapeColumns([])
            ->only(['lokasi_id', 'nama_lokasi', 'koefisien', 'bulan', 'nama_bulan', 'minggu', 'minggu_label', 'tanggal',
                'kecamatan', 'desa', 'nama_kepala_keluarga', 'alamat_rumah', ...self::KOLOM_HITUNG])
            ->make(true);
    }

    // Data Sampah detail per log + subtotal, mengikuti filter & klaster aktif (stream)
    public function export(RekapSampahRequest $request, PenguranganSampahService $sampah)
    {
        $data = $this->dataUser($request, $sampah);
        $filter = $data['filter'];

        return (new DataSampahExport($sampah, $filter, $data['mingguan'], $data['subtotal']))
            ->info([
                'Bulan' => $filter['bulan'] ? Carbon::createFromFormat('!Y-m', $filter['bulan'])->translatedFormat('F Y') : 'Semua',
                'Lokasi Program' => $filter['lokasi'] ? LokasiProgram::whereKey($filter['lokasi'])->value('nama_lokasi') : null,
                'Klaster' => PenguranganSampah::KLASTER[$filter['klaster'] ?? '']['label'] ?? null,
            ])
            ->download('data_sampah_'.($filter['klaster'] ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    // Scope user login: PT dikunci ke PT-nya sendiri (filter kodept/klaster diabaikan), kolom Minggu hanya PEMDA
    private function dataUser(RekapSampahRequest $request, PenguranganSampahService $sampah): array
    {
        $user = $request->user();
        $isPt = $user->role === 'pt';
        $input = $request->safe()->only($isPt ? ['bulan', 'kecamatan', 'lokasi'] : ['bulan', 'kecamatan', 'kodept', 'klaster', 'lokasi']);

        return $this->data($input, $sampah, $isPt ? $user->email : null, $user->role === 'pemda');
    }

    /**
     * Di-cache per filter + scope (PT hanya datanya sendiri); versi berganti saat data sampah berubah.
     *
     * @param  string|null  $ptScope  kodept bila user PT (dikunci ke PT-nya sendiri)
     * @param  bool  $mingguan  kolom & subtotal Minggu (hanya PEMDA)
     */
    private function data(array $input, PenguranganSampahService $sampah, ?string $ptScope, bool $mingguan): array
    {
        $scope = $ptScope !== null ? 'pt:'.$ptScope : 'all';
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0');

        return Cache::remember('rekapsampah.v5.'.$versi.'.'.md5($scope.'|'.(int) $mingguan.'|'.json_encode($input)), now()->addMinutes(10), function () use ($input, $sampah, $ptScope, $mingguan) {
            [$filter, $klasterPt] = $this->filter($input, $sampah, $ptScope);

            return [
                'filter' => $filter,
                'klasterPt' => $klasterPt,
                'subtotal' => $sampah->dataSampahSubtotal($filter, $mingguan),
                'mingguan' => $mingguan,
                'bulanList' => $sampah->bulanList($ptScope),
            ];
        });
    }

    /**
     * @return array{0: array, 1: Collection} filter query & klaster tiap PT (kosong untuk role PT)
     */
    private function filter(array $input, PenguranganSampahService $sampah, ?string $ptScope): array
    {
        $kodept = $ptScope ?? ($input['kodept'] ?? null);

        // PT dikunci ke PT-nya sendiri; bulan default = bulan terakhir yang ada datanya, "semua" = tanpa filter bulan
        $bulan = $input['bulan'] ?? null;
        $bulan = $bulan === 'semua' ? null : ($bulan ?: $sampah->bulanTerakhir($kodept));
        $filter = ['bulan' => $bulan, 'id_kecamatan' => $input['kecamatan'] ?? null, 'kodept' => $kodept, 'lokasi' => $input['lokasi'] ?? null];

        if ($ptScope !== null) {
            return [$filter + ['klaster' => null], collect()];
        }

        // Klaster dihitung sebelum dipilih agar jumlah PT tiap klaster tetap tampil
        $klasterPt = $sampah->klasterPt($filter);
        $filter['klaster'] = $input['klaster'] ?? null;
        if ($filter['klaster']) {
            $filter['kodept_in'] = $klasterPt->where('klaster', $filter['klaster'])->keys()->all();
        }

        return [$filter, $klasterPt];
    }
}
