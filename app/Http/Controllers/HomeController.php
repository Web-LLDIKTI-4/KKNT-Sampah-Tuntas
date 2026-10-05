<?php

namespace App\Http\Controllers;

use App\Models\Dpl;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, DashboardService $dashboard, ?string $lokasi = null)
    {
        $user = $request->user();

        // Samakan URL dengan lokasi program yang dipilih saat login
        $lokasiSession = session('lokasi_program');
        if (in_array($user->role, ['dpl', 'mahasiswa'], true) && $lokasiSession
            && mb_strtolower((string) $lokasi) !== mb_strtolower($lokasiSession)) {
            return redirect(url('home/'.rawurlencode(mb_strtolower($lokasiSession))));
        }

        return match ($user->role) {
            'mahasiswa' => view('index-user', $dashboard->forMahasiswa($user)),
            'dpl' => Dpl::where('email', $user->email)->exists()
                ? view('index-admin', $dashboard->forDpl($user))
                : redirect(url('profile')),
            'pt' => view('index-admin', $dashboard->forPt($user)),
            'kepala', 'pemda' => view('index-admin', $dashboard->forKepala()),
            default => view('index-admin', $dashboard->forAdmin()),
        };
    }
}
