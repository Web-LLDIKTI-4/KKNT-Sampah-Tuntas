<?php

namespace App\Http\Controllers;

use App\Exports\DataPesertaExport;
use App\Http\Requests\Auth\LaporanPublikRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PesertaDetailRequest;
use App\Http\Requests\Auth\PetaSebaranRequest;
use App\Models\PenguranganSampah;
use App\Models\LokasiProgram;
use App\Models\Panduan;
use App\Services\DataPesertaSummary;
use App\Services\PenguranganSampahService;
use App\Services\LokasiProgramSummary;
use App\Services\PetaSebaranService;
use App\Services\RekapPenguranganSampahService;
use App\Services\SebaranPtSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    // Landing publik: tamu & user login (user login dapat tombol "Ke Dashboard" di view)
    public function landing(LokasiProgramSummary $summary, PenguranganSampahService $sampah, DataPesertaSummary $peserta, RekapPenguranganSampahService $rekap, SebaranPtSummary $sebaran)
    {
        return view('landing', [
            'lokasiProgramList' => $summary->all(),
            'dataPesertaList' => $this->dataPeserta($peserta),
            'sebaranPtList' => $sebaran->cached(),
            'laporan' => $this->laporanData($sampah, ['bulan' => null, 'id_kecamatan' => null, 'id_desa' => null, 'klaster' => null]) + ['publik' => true],
            'panduanList' => $this->getPanduanPublik(),
        ] + $this->statistikPublik($rekap));
    }

    // Form login saja; ?lokasi= hanya dipakai bila cocok dengan master (tidak di-echo mentah)
    public function index(Request $request)
    {
        $lokasiProgramList = LokasiProgram::orderBy('nama_lokasi')->get(['id', 'nama_lokasi']);
        $query = $request->query('lokasi');
        // Non-string (lokasi[]=x) diabaikan
        $query = is_string($query) ? mb_strtolower(trim($query)) : '';
        $selectedLokasi = $query === '' ? null
            : $lokasiProgramList->first(fn ($l) => mb_strtolower($l->nama_lokasi) === $query)?->nama_lokasi;

        return view('login', [
            'lokasiProgramList' => $lokasiProgramList,
            'selectedLokasi' => $selectedLokasi,
        ]);
    }

    // Laporan berjenjang kecamatan -> kelurahan -> kelompok sesuai pilihan bulan/kecamatan/kelurahan
    public function laporan(LaporanPublikRequest $request, PenguranganSampahService $sampah)
    {
        return view('laporan._capaian_publik', $this->laporanData($sampah, $request->filter()) + ['publik' => true]);
    }

    // Peta sebaran mahasiswa per desa (publik, tanpa data pribadi, angka kecil disamarkan)
    public function peta(PetaSebaranRequest $request, PetaSebaranService $peta)
    {
        $filter = $request->filter();

        return response()->json(Cache::remember('login.peta.v4.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1).'.'.Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0').'.'.$filter['tahun'].'.'.($filter['kodept'] ?? 'all'), now()->addMinutes(10), fn () => $peta->sebaran($filter)));
    }

    // Detail peserta per PT untuk modal tab Data Peserta (tidak disamarkan, sesuai keputusan pemilik)
    public function peserta(PesertaDetailRequest $request, DataPesertaSummary $peserta)
    {
        $kodept = $request->validated('kodept');
        $jenis = $request->validated('jenis');

        // Only PT listed in the summary, so cache keys stay bounded
        // Strict compare: loose == matches numeric variants like 0+kode or kode+e0
        $pt = $this->dataPeserta($peserta)->firstWhere('kodept', '===', $kodept);
        abort_if($pt === null, 404);
        $kodept = $pt->kodept;

        $key = 'login.data_peserta.detail.v2.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1).'.'.$kodept.'.'.$jenis;

        return response()->json(Cache::remember($key, now()->addMinutes(10), fn () => [
            'nama_pt' => $pt->nama_pt,
            'jenis' => $jenis,
            'data' => $peserta->members($kodept, $jenis),
        ]));
    }

    // Same cached list as the table on /login, so no re-query
    public function dataPesertaExport(DataPesertaSummary $peserta)
    {
        return (new DataPesertaExport($this->dataPeserta($peserta)))->download('data_peserta_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    public function petaFilter(PetaSebaranService $peta)
    {
        return response()->json(Cache::remember(PetaSebaranService::FILTER_CACHE_KEY, now()->addMinutes(10), fn () => $peta->options()));
    }

    public function proseslogin(LoginRequest $request)
    {
        if (! $request->authenticate()) {
            return LoginRequest::failedResponse('Username atau Kata Sandi Salah');
        }

        $user = Auth::user();
        $redirect = url('home');

        if (in_array($user->role, ['dpl', 'mahasiswa'], true)) {
            $lokasi = $this->resolveLokasi($user, trim((string) $request->input('lokasi')));
            if (is_string($lokasi) === false) {
                Auth::logout();

                return LoginRequest::failedResponse($lokasi['error']);
            }
            $redirect = url('home/'.rawurlencode(mb_strtolower($lokasi)));
        }

        // Cegah session fixation
        $request->session()->regenerate();
        isset($lokasi) ? session(['lokasi_program' => $lokasi]) : session()->forget('lokasi_program');

        $user->forceFill(['last_login' => now()])->save();

        return response()->json(['success' => true, 'messages' => 'proses login...', 'redirect_url' => $redirect]);
    }

    // Token CSRF baru untuk form login yang sesinya sudah kedaluwarsa (419)
    public function token(Request $request)
    {
        $request->session()->regenerateToken();

        return response()->json(['token' => csrf_token()]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Hanya kolom aman untuk publik; file_path & uploader tidak boleh sampai ke HTML.
    // Same definitions as admin dashboard; uncached so header stays in sync
    private function statistikPublik(RekapPenguranganSampahService $rekap): array
    {
        $total = $rekap->registeredTotals();

        return [
            'jumlahMahasiswa' => $total['mahasiswa'],
            'jumlahDpl' => $total['dpl'],
            'jumlahPt' => $total['pt'],
            'jumlahKecamatan' => $total['kecamatan'],
            'jumlahKelurahan' => $total['kelurahan'],
        ];
    }

    private function dataPeserta(DataPesertaSummary $peserta)
    {
        return Cache::remember('login.data_peserta.v3.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1), now()->addMinutes(10), fn () => $peserta->all());
    }

    // Cache di-forget oleh hook model Panduan setiap ada perubahan
    private function getPanduanPublik()
    {
        return Cache::remember(Panduan::PUBLIC_CACHE_KEY, now()->addMinutes(10), fn () => Panduan::query()
            ->where('is_aktif', true)
            ->latest('updated_at')
            ->get(['id_panduan', 'judul', 'deskripsi', 'nama_file', 'ukuran', 'mime', 'updated_at']));
    }

    // Halaman publik: di-cache per kombinasi filter agar query berat tidak jalan di setiap kunjungan
    private function laporanData(PenguranganSampahService $sampah, array $filter): array
    {
        // Versi peta ikut: berubah saat master desa (mis. baseline) disimpan
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0').'.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1);

        return Cache::remember('login.capaian.v4.'.$versi.'.'.md5(json_encode($filter)), now()->addMinutes(10), fn () => $sampah->drilldownPublik($filter));
    }

    /**
     * @return string|array{error: string} nama lokasi baku, atau pesan error
     */
    private function resolveLokasi($user, string $lokasi): string|array
    {
        if ($lokasi === '') {
            return ['error' => 'Silakan pilih lokasi program terlebih dahulu'];
        }
        if (! $user->location_program) {
            return ['error' => 'Anda belum memiliki lokasi program kkn, silahkan hubungi admin untuk menambahkan lokasi program anda!'];
        }

        $master = LokasiProgram::whereKey($user->location_program)
            ->whereRaw('LOWER(nama_lokasi) = ?', [mb_strtolower($lokasi)])
            ->value('nama_lokasi');

        return $master ?? ['error' => 'Lokasi program anda tidak valid!'];
    }
}
