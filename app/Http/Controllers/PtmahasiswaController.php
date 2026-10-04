<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PtmahasiswaController extends Controller
{
    public function index()
    {
        return view('mahasiswa.pt.index');
    }

    public function listdata()
    {
        return view('mahasiswa.pt.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Mahasiswa::visibleTo($request->user())->with(['sp', 'locationProgram'])
            ->withExists('pjdesa as ketua_kelompok')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nm_lemb', fn ($row) => $row->sp->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->locationProgram->nama_lokasi ?? 'Belum Terdata')
            ->make(true);
    }
}
