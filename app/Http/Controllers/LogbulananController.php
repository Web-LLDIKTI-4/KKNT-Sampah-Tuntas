<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\LaporanBulananRequest;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LogbulananController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        $namaBulan = [];
        foreach (range(1, 12) as $i) {
            $namaBulan[$i] = Carbon::create()->month($i)->format('F');
        }

        return view('logbulanan.mahasiswa.index', compact('namaBulan'));
    }

    public function tambah(Request $request)
    {
        $periode = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
        ]);
        $user = $request->user();

        return view('logbulanan.mahasiswa.tambah', [
            'bulan' => $periode['bulan'],
            'tahun' => $periode['tahun'],
            'isi' => Logbulanan::ownedBy($user)->where($periode)->first(),
            'logharian' => Logkegiatan::ownedBy($user)
                ->whereNotNull('deskripsi')
                ->whereYear('tanggal', $periode['tahun'])
                ->whereMonth('tanggal', $periode['bulan'])
                ->orderBy('tanggal')
                ->get(),
        ]);
    }

    public function insert(LaporanBulananRequest $request)
    {
        $user = $request->user();

        Logbulanan::updateOrCreate(
            ['email' => $user->email, 'bulan' => $request->validated('bulan'), 'tahun' => $request->validated('tahun')],
            $request->safe()->only('tautan', 'deskripsi')
        );

        return $this->saved('Log kegiatan bulanan berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $deleted = Logbulanan::ownedBy($request->user())->whereKey($request->input('id_logbulanan'))->delete();

        return $deleted
            ? $this->deleted('Log kegiatan bulanan berhasil dihapus')
            : $this->failed('Log kegiatan bulanan gagal dihapus');
    }

    public function listdata(Request $request)
    {
        $laporan = Logbulanan::ownedBy($request->user())->orderByDesc('tahun')->orderByDesc('bulan')->get();

        return view('logbulanan.mahasiswa.listdata', compact('laporan'));
    }
}
