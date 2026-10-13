<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PicpjController extends Controller
{
    public function index()
    {
        return view('picpj.index');
    }

    public function listdata()
    {
        return view('picpj.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        // PT users store kode PT (npsn) in users.email
        $query = User::query()
            ->without(['mahasiswa', 'dpl'])
            ->where('users.role', 'pt')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'users.email')
            ->select('users.email as kodept', 'users.name', 'users.phone', 'sp.nm_lemb', 'sp.no_tel')
            // Stable default order; only when no user-orderable column (yajra appends user order after base order)
            ->when(! DataTables::getRequest()->orderableColumns(), fn ($q) => $q->orderBy('users.email'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist(['kodept', 'nm_lemb', 'no_tel'])
            ->editColumn('nm_lemb', fn ($row) => $row->nm_lemb ?: $row->name)
            // Prefer PT contact (users.phone), fallback to campus phone
            ->editColumn('no_tel', fn ($row) => $row->phone ?: ($row->no_tel ?: '-'))
            ->filterColumn('kodept', fn ($query, $keyword) => $query->where('users.email', 'like', "%{$keyword}%"))
            ->filterColumn('nm_lemb', fn ($query, $keyword) => $query->where(fn ($q) => $q
                ->where('sp.nm_lemb', 'like', "%{$keyword}%")
                ->orWhere('users.name', 'like', "%{$keyword}%")))
            ->filterColumn('no_tel', fn ($query, $keyword) => $query->whereRaw("COALESCE(NULLIF(users.phone, ''), sp.no_tel) like ?", ["%{$keyword}%"]))
            ->orderColumn('kodept', 'users.email $1')
            ->orderColumn('nm_lemb', 'COALESCE(sp.nm_lemb, users.name) $1')
            ->orderColumn('no_tel', "COALESCE(NULLIF(users.phone, ''), sp.no_tel) \$1")
            ->removeColumn('name', 'phone')
            ->make(true);
    }
}
