<?php

namespace App\Http\Controllers;

use App\Models\Dpl;
use App\Support\PersonDataTable;
use Illuminate\Http\Request;

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

        return PersonDataTable::make(Dpl::visibleTo($request->user()), ['nidn', 'nama', 'email', 'phone'])
            ->make(true);
    }
}
