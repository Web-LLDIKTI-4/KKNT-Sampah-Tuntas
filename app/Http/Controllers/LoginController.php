<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LaporanPublikRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PetaSebaranRequest;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Kecamatan;
use App\Models\Mahasiswa;
use App\Models\PenguranganSampah;
use App\Models\LokasiProgram;
use App\Models\Panduan;
use App\Services\PenguranganSampahService;
use App\Services\LokasiProgramSummary;
use App\Services\PetaSebaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    public function index(LokasiProgramSummary $summary, PenguranganSampahService $sampah)
    {
        return view('login', [
            'lokasiProgramList' => $summary->all(),
            'laporan' => $this->laporanData($sampah, ['bulan' => null, 'id_kecamatan' => null, 'id_desa' => null, 'klaster' => null]),
            'panduanList' => $this->getPanduanPublik(),
        ] + $this->statistikPublik());
    }

    // Laporan berjenjang kecamatan -> kelurahan -> kelompok sesuai pilihan bulan/kecamatan/kelurahan
    public function laporan(LaporanPublikRequest $request, PenguranganSampahService $sampah)
    {
        return view('laporan._capaian_publik', $this->laporanData($sampah, $request->filter()));
    }

    // Peta sebaran mahasiswa per desa (publik, tanpa data pribadi, angka kecil disamarkan)
    public function peta(PetaSebaranRequest $request, PetaSebaranService $peta)
    {
        $filter = $request->filter();

        return response()->json(Cache::remember('login.peta.v4.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1).'.'.Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0').'.'.$filter['tahun'].'.'.($filter['kodept'] ?? 'all'), now()->addMinutes(10), fn () => $peta->sebaran($filter)));
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
    // Same definitions as admin dashboard; version key bumped by PetaSebaranCacheObserver
    private function statistikPublik(): array
    {
        $key = 'login.statistik.v1.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1);

        return array_map(fn ($v) => number_format($v, 0, ',', '.'), Cache::remember($key, now()->addMinutes(10), fn () => [
            'jumlahMahasiswa' => Mahasiswa::count(),
            'jumlahDpl' => Dpl::count(),
            'jumlahPt' => Mahasiswa::whereNotNull('kodept')->distinct()->count('kodept'),
            'jumlahKecamatan' => Kecamatan::count(),
            'jumlahKelurahan' => Desa::count(),
        ]));
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
        $versi = Cache::get(PenguranganSampah::PUBLIC_VERSION_CACHE_KEY, '0');

        return Cache::remember('login.capaian.v3.'.$versi.'.'.md5(json_encode($filter)), now()->addMinutes(10), fn () => $sampah->drilldownPublik($filter));
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
