<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PtmahasiswaController extends Controller
{
    use HandlesDataTablesOrder;

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
        $this->sanitizeDataTablesInput();

        $whitelist = ['nim', 'nama', 'email', 'phone', 'nm_lemb', 'location_program'];
        // Joins replace eager loads: npsn is unique and lokasi_program.id is PK, so no duplicate rows
        $query = Mahasiswa::visibleTo($request->user())
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'mahasiswa.kodept')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'mahasiswa.location_program')
            ->select('mahasiswa.id_mahasiswa', 'mahasiswa.nim', 'mahasiswa.nama', 'mahasiswa.email', 'mahasiswa.phone', 'sp.nm_lemb', 'lp.nama_lokasi')
            ->withExists('pjdesa as ketua_kelompok')
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('mahasiswa.nama')->orderBy('mahasiswa.id_mahasiswa'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->addColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->nama_lokasi ?? 'Belum Terdata')
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->where('sp.nm_lemb', 'like', $this->likeContains($k)))
            ->orderColumn('nm_lemb', 'sp.nm_lemb $1')
            ->filterColumn('location_program', fn ($q, $k) => $q->where('lp.nama_lokasi', 'like', $this->likeContains($k)))
            ->orderColumn('location_program', 'lp.nama_lokasi $1')
            ->make(true);
    }
}
