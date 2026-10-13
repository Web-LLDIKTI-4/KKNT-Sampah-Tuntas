<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PicpjController extends Controller
{
    use HandlesDataTablesOrder;

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
        $this->sanitizeDataTablesInput();
        $whitelist = ['kodept', 'nm_lemb', 'no_tel'];

        // PT users store kode PT (npsn) in users.email
        $query = User::query()
            ->without(['mahasiswa', 'dpl'])
            ->where('users.role', 'pt')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'users.email')
            ->select('users.email as kodept', 'users.name', 'sp.nm_lemb', 'users.phone as no_tel')
            // Stable default order; only when no user-orderable column (yajra appends user order after base order)
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('users.email'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->editColumn('nm_lemb', fn ($row) => $row->nm_lemb ?: $row->name)
            // No. Kontak comes only from users.phone
            ->editColumn('no_tel', fn ($row) => $row->no_tel ?: '-')
            ->filterColumn('kodept', fn ($query, $keyword) => $query->where('users.email', 'like', "%{$keyword}%"))
            ->filterColumn('nm_lemb', fn ($query, $keyword) => $query->where(fn ($q) => $q
                ->where('sp.nm_lemb', 'like', "%{$keyword}%")
                ->orWhere('users.name', 'like', "%{$keyword}%")))
            ->filterColumn('no_tel', fn ($query, $keyword) => $query->where('users.phone', 'like', "%{$keyword}%"))
            ->orderColumn('kodept', 'users.email $1')
            ->orderColumn('nm_lemb', 'COALESCE(sp.nm_lemb, users.name) $1')
            ->orderColumn('no_tel', 'users.phone $1')
            ->removeColumn('name', 'phone')
            ->make(true);
    }
}
