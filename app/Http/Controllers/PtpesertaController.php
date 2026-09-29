<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
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

        $data = Mahasiswa::groupBy('kodept')->selectRaw('kodept, count(*) as jumlah_mhs')->get();
        $nama = Satuanpendidikan::whereIn('npsn', $data->pluck('kodept')->filter())->pluck('nm_lemb', 'npsn');

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nm_lemb', fn ($row) => $nama[$row->kodept] ?? 'Perguruan Tinggi tidak ditemukan')
            ->make(true);
    }
}
