<?php

namespace App\Http\Controllers;

use App\Models\Dpl;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PtdplController extends Controller
{
    public function index()
    {
        return view('dpl.pt.index');
    }

    public function listdata()
    {
        return view('dpl.pt.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Dpl::visibleTo($request->user())->with(['sp', 'locationProgram'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nm_lemb', fn ($row) => $row->sp->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->locationProgram->nama_lokasi ?? 'Belum Terdata')
            ->make(true);
    }
}
