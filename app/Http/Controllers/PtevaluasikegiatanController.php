<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Evaluasi\JawabanRequest;
use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;

class PtevaluasikegiatanController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('evaluasikegiatan.pt.index');
    }

    public function tambah()
    {
        $user = auth()->user()->email;
        $tahun = (int) date('Y');
        $jawaban = Evaluasikegiatanjawaban::where('user', $user)->where('tahun', $tahun)->get()->keyBy('id_evaluasi');

        $evaluasi = Evaluasikegiatan::orderBy('created_at')->get()
            ->map(fn ($item) => ['evaluasi' => $item, 'jawaban' => $jawaban[$item->id_evaluasi] ?? null])
            ->all();

        return view('evaluasikegiatan.pt.tambah', ['evaluasi' => $evaluasi]);
    }

    public function insert(JawabanRequest $request)
    {
        $user = auth()->user()->email;
        $tahun = (int) date('Y');
        $jawaban = $request->validated('jawaban');

        // Hanya simpan jawaban untuk pertanyaan yang benar-benar ada
        $validIds = Evaluasikegiatan::whereIn('id_evaluasi', array_keys($jawaban))->pluck('id_evaluasi');

        foreach ($validIds as $idEvaluasi) {
            Evaluasikegiatanjawaban::updateOrCreate(
                ['id_evaluasi' => $idEvaluasi, 'user' => $user, 'tahun' => $tahun],
                ['jawaban' => $jawaban[$idEvaluasi], 'kodept' => $user]
            );
        }

        return $this->saved('Data berhasil disimpan!');
    }
}
