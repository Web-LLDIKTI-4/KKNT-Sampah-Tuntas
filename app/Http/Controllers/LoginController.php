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
            return response()->json(['success' => false, 'messages' => 'Email atau Password Salah']);
        }

        $user = Auth::user();
        $redirect = url('home');

        if (in_array($user->role, ['dpl', 'mahasiswa', 'pt'], true)) {
            $lokasi = $this->resolveLokasi($user, trim((string) $request->input('lokasi')));
            if (is_string($lokasi) === false) {
                Auth::logout();

                return response()->json(['success' => false, 'messages' => $lokasi['error']]);
            }
            $redirect = url('home/'.rawurlencode(mb_strtolower($lokasi)));
        }

        // Cegah session fixation
        $request->session()->regenerate();
        isset($lokasi) ? session(['lokasi_program' => $lokasi]) : session()->forget('lokasi_program');

        $user->forceFill(['last_login' => now()])->save();

        return response()->json(['success' => true, 'messages' => 'proses login...', 'redirect_url' => $redirect]);
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
