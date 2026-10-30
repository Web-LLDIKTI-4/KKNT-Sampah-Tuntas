<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Pt\MobilisasiRequest;
use App\Models\Pjdesa;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

class PtpjdesaController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    public function index()
    {
        return view('ptpjdesa.index');
    }

    public function listdata()
    {
        return view('ptpjdesa.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $whitelist = ['kecamatan', 'desa', 'pjdesa', 'mobilisasi'];
        $mobilisasi = $request->query('mobilisasi');

        $query = Pjdesa::visibleTo($request->user())
            ->leftJoin('desa', 'desa.id_desa', '=', 'pj_desa.id_desa')
            ->leftJoin('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.id_kecamatan')
            ->select('pj_desa.id_pjdesa', 'pj_desa.email', 'pj_desa.status_mobilisasi', 'desa.desa as nama_desa', 'kecamatan.kecamatan as nama_kecamatan')
            ->when(in_array($mobilisasi, Pjdesa::MOBILISASI_STATUSES, true), fn ($q) => $q->where('pj_desa.status_mobilisasi', $mobilisasi))
            ->with('mahasiswa')
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q
                ->orderBy('kecamatan.kecamatan')
                ->orderBy('desa.desa')
                ->orderBy('pj_desa.id_pjdesa'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->addColumn('kecamatan', fn (Pjdesa $row) => $row->nama_kecamatan ?? '')
            ->addColumn('desa', fn (Pjdesa $row) => $row->nama_desa ?? '')
            ->addColumn('pjdesa', fn (Pjdesa $row) => $row->mahasiswa->nama ?? 'Data tidak tersedia')
            ->addColumn('mobilisasi', fn (Pjdesa $row) => view('pjdesa._mobilisasi-badge', ['status' => $row->status_mobilisasi])->render())
            ->filterColumn('kecamatan', fn ($q, $k) => $q->where('kecamatan.kecamatan', 'like', $this->likeContains($k)))
            ->filterColumn('desa', fn ($q, $k) => $q->where('desa.desa', 'like', $this->likeContains($k)))
            ->filterColumn('pjdesa', fn ($q, $k) => $this->whereRelatedLike($q, 'mahasiswa', 'nama', $k))
            ->orderColumn('kecamatan', 'kecamatan.kecamatan $1')
            ->orderColumn('desa', 'desa.desa $1')
            // mahasiswa.email tidak unik: subquery deterministik
            ->orderColumn('pjdesa', '(SELECT m.nama FROM mahasiswa m WHERE m.email = pj_desa.email ORDER BY m.id_mahasiswa DESC LIMIT 1) $1')
            ->orderColumn('mobilisasi', 'pj_desa.status_mobilisasi $1')
            ->addColumn('action', fn (Pjdesa $row) => ActionButtons::make(urlEdit: route('ptpjdesa.edit', $row->id_pjdesa)))
            ->rawColumns(['mobilisasi', 'action'])
            // Jangan kirim email/id_desa ke browser
            ->only(['DT_RowIndex', 'kecamatan', 'desa', 'pjdesa', 'mobilisasi', 'action'])
            ->make(true);
    }

    public function edit(Request $request, string $id_pjdesa)
    {
        $pjdesa = Pjdesa::visibleTo($request->user())->with(['desa.kecamatan', 'mahasiswa'])->findOrFail($id_pjdesa);
        Gate::authorize('updateMobilisasi', $pjdesa);

        return view('ptpjdesa.edit', ['pjdesa' => $pjdesa]);
    }

    public function update(MobilisasiRequest $request)
    {
        // Scope visibleTo: kelompok PT lain dianggap tidak ada (404)
        $pjdesa = Pjdesa::visibleTo($request->user())->find($request->validated('id_pjdesa'));
        if (! $pjdesa) {
            return $this->notFound();
        }
        if (Gate::denies('updateMobilisasi', $pjdesa)) {
            return $this->deleteRejected('Anda tidak memiliki akses untuk data ini.', 403);
        }

        $pjdesa->update(['status_mobilisasi' => $request->validated('status_mobilisasi')]);

        return $this->saved();
    }
}
