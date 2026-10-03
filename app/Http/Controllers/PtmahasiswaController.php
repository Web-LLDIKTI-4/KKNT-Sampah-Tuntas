<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Support\PersonDataTable;
use Illuminate\Http\Request;

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

        return PersonDataTable::make(Mahasiswa::visibleTo($request->user()), ['nim', 'nama', 'email', 'phone'])
            ->make(true);
    }
}
