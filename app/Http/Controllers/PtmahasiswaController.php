<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Concerns\SortsLatestLokasi;
use App\Http\Requests\Pt\CabutKetuaRequest;
use App\Http\Requests\Pt\SetKetuaRequest;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Services\KetuaKelompokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

class PtmahasiswaController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson, SortsLatestLokasi;

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
        $isPt = $request->user()->role === 'pt';
        // Correlated subquery (no pj_desa join: duplicate emails would multiply rows)
        $ketua = fn () => Pjdesa::whereColumn('pj_desa.email', 'mahasiswa.email')
            ->oldest('pj_desa.created_at')->orderBy('pj_desa.id_pjdesa')->limit(1);
        // Joins replace eager loads: npsn is unique and lokasi_program.id is PK, so no duplicate rows
        $query = Mahasiswa::visibleTo($request->user())
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'mahasiswa.kodept')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'mahasiswa.location_program')
            ->select('mahasiswa.id_mahasiswa', 'mahasiswa.nim', 'mahasiswa.nama', 'mahasiswa.email', 'mahasiswa.phone', 'sp.nm_lemb', 'lp.nama_lokasi')
            ->addSelect(['ketua_id' => $ketua()->select('pj_desa.id_pjdesa')])
            ->when($isPt, fn ($q) => $q->withExists(['user as punya_akun' => fn ($u) => $u->where('role', 'mahasiswa')]))
            ->withLatestLokasi()
            ->lokasiStatus($request->query('lokasi_status'))
            ->ketuaKelompok($request->query('ketua_kelompok'))
            ->when(! $this->hasClientOrder([...$whitelist, ...array_keys(Mahasiswa::LOKASI_COLUMNS)]), fn ($q) => $q->orderBy('mahasiswa.nama')->orderBy('mahasiswa.id_mahasiswa'));

        $dataTable = DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->addColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->nama_lokasi ?? 'Belum Terdata')
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->where('sp.nm_lemb', 'like', $this->likeContains($k)))
            ->orderColumn('nm_lemb', 'sp.nm_lemb $1')
            ->filterColumn('location_program', fn ($q, $k) => $q->where('lp.nama_lokasi', 'like', $this->likeContains($k)))
            ->orderColumn('location_program', 'lp.nama_lokasi $1')
            ->addColumn('ketua_kelompok', fn ($row) => $row->ketua_id !== null);
        $rawColumns = [];

        if ($isPt) {
            $dataTable->addColumn('action', fn ($row) => view('mahasiswa.pt._aksi-ketua', ['row' => $row])->render());
            $rawColumns[] = 'action';
        }
        $dataTable->rawColumns($rawColumns);

        return $this->latestLokasiColumns($dataTable)->make(true);
    }

    public function setKetua(SetKetuaRequest $request, KetuaKelompokService $ketua)
    {
        // Scope visibleTo: mahasiswa PT lain dianggap tidak ada (404)
        $mahasiswa = Mahasiswa::visibleTo($request->user())->find($request->validated('id_mahasiswa'));
        if (! $mahasiswa) {
            return $this->notFound();
        }
        if (Gate::denies('assign', [Pjdesa::class, $mahasiswa])) {
            return $this->deleteRejected('Anda tidak memiliki akses untuk data ini.', 403);
        }

        if ($error = $ketua->assign($mahasiswa)) {
            return $this->failed($error);
        }

        return $this->saved('Ketua kelompok berhasil ditetapkan');
    }

    public function cabutKetua(CabutKetuaRequest $request, KetuaKelompokService $ketua)
    {
        $pjdesa = Pjdesa::visibleTo($request->user())->find($request->validated('id_pjdesa'));
        if (! $pjdesa) {
            return $this->notFound();
        }
        if (Gate::denies('revoke', $pjdesa)) {
            return $this->deleteRejected('Anda tidak memiliki akses untuk data ini.', 403);
        }

        if ($error = $ketua->revoke($pjdesa)) {
            return $this->deleteRejected($error);
        }

        return $this->deleted('Akses ketua kelompok berhasil dicabut');
    }
}
