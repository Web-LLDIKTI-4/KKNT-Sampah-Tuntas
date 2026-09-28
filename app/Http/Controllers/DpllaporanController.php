<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\LaporanBulananRequest;
use App\Models\Dpl;
use App\Models\Dpllaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DpllaporanController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        if (! Dpl::where('email', $request->user()->email)->exists()) {
            return redirect(url('profile'));
        }

        $namaBulan = [];
        foreach (range(1, 12) as $i) {
            $namaBulan[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        return view('laporan.index', compact('namaBulan'));
    }

    public function tambah(Request $request)
    {
        $periode = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
        ]);

        return view('laporan.tambah', $periode + [
            'isi' => Dpllaporan::where('email', $request->user()->email)->where($periode)->first(),
        ]);
    }

    public function insert(LaporanBulananRequest $request)
    {
        Dpllaporan::updateOrCreate(
            ['email' => $request->user()->email, 'bulan' => $request->validated('bulan'), 'tahun' => $request->validated('tahun')],
            $request->safe()->only('tautan', 'deskripsi')
        );

        return $this->saved('Log kegiatan bulanan berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $deleted = Dpllaporan::where('email', $request->user()->email)->whereKey($request->input('id_laporan'))->delete();

        return $deleted
            ? $this->deleted('Log kegiatan bulanan berhasil dihapus')
            : $this->failed('Log kegiatan bulanan gagal dihapus');
    }

    public function listdata(Request $request)
    {
        $laporan = Dpllaporan::where('email', $request->user()->email)->orderByDesc('tahun')->orderByDesc('bulan')->get();

        return view('laporan.listdata', compact('laporan'));
    }
}
