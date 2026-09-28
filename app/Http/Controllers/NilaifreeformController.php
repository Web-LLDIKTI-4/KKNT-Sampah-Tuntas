<?php

namespace App\Http\Controllers;

use App\Models\Freeform;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use Illuminate\Http\Request;

class NilaifreeformController extends Controller
{
    public function index(Request $request)
    {
        return view('nilaifreeform.index', ['mahasiswa' => $this->mahasiswa($request)]);
    }

    public function nilaikonversi(Request $request)
    {
        $mahasiswa = $this->mahasiswa($request);

        return view('nilaifreeform.nilaikonversi', [
            'mahasiswa' => $mahasiswa,
            'nilai' => Nilaikonversi::where('id_mahasiswa', $mahasiswa->id_mahasiswa)->get(),
        ]);
    }

    public function freeform(Request $request)
    {
        $mahasiswa = $this->mahasiswa($request);

        return view('nilaifreeform.freeform', [
            'mahasiswa' => $mahasiswa,
            'nilai' => Freeform::where('id_mahasiswa', $mahasiswa->id_mahasiswa)->get(),
        ]);
    }

    // Halaman nilai hanya untuk akun yang punya data mahasiswa
    private function mahasiswa(Request $request): Mahasiswa
    {
        $mahasiswa = Mahasiswa::where('email', $request->user()->email)->first();
        abort_unless($mahasiswa, 404);

        return $mahasiswa;
    }
}
