<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\LokasiProgram;
use App\Services\LokasiProgramSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index(LokasiProgramSummary $summary)
    {
        return view('login', ['lokasiProgramList' => $summary->all()]);
    }

    public function proseslogin(LoginRequest $request)
    {
        if (! $request->authenticate()) {
            return LoginRequest::failedResponse('Email atau Password Salah');
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
