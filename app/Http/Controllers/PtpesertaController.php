<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PtpesertaController extends Controller
{
    public function index()
    {
        return view('ptpeserta.index');
    }

    public function listdata()
    {
        return view('ptpeserta.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        // Join agar nm_lemb bisa dicari & diurutkan di SQL
        $query = Mahasiswa::query()
            ->selectRaw('mahasiswa.kodept, ref_satuanpendidikan.nm_lemb, count(*) as jumlah_mhs')
            ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'mahasiswa.kodept')
            ->groupBy('mahasiswa.kodept', 'ref_satuanpendidikan.nm_lemb');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Perguruan Tinggi tidak ditemukan')
            ->filterColumn('kodept', fn ($q, $keyword) => $q->where('mahasiswa.kodept', 'like', "%{$keyword}%"))
            ->filterColumn('nm_lemb', fn ($q, $keyword) => $q->where('ref_satuanpendidikan.nm_lemb', 'like', "%{$keyword}%"))
            ->orderColumn('nm_lemb', 'ref_satuanpendidikan.nm_lemb $1')
            ->orderColumn('jumlah_mhs', 'jumlah_mhs $1')
            ->blacklist(['jumlah_mhs'])
            // Cegah pencarian kolom mahasiswa.* lewat columns[name] dari request
            ->whitelist(['kodept', 'nm_lemb', 'jumlah_mhs'])
            ->make(true);
    }
}
