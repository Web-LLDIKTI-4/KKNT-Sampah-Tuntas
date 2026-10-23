<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\CapaianKegiatan;
use App\Models\Pjdesa;
use App\Models\User;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PjdesaController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    public function index()
    {
        return view('pjdesa.index');
    }

    public function listdata()
    {
        return view('pjdesa.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $whitelist = ['kecamatan', 'desa', 'pjdesa', 'instansi'];
        // mahasiswa.email is not unique: subquery (deterministic) instead of join to avoid duplicate rows
        $mahasiswaCol = fn (string $col) => "(SELECT {$col} FROM mahasiswa m LEFT JOIN ref_satuanpendidikan sp ON sp.npsn = m.kodept"
            .' WHERE m.email = pj_desa.email ORDER BY m.id_mahasiswa DESC LIMIT 1)';
        $mobilisasi = $request->query('mobilisasi');

        // desa/kecamatan joined on PK (no duplicates); capaian = current-year count per row
        $query = Pjdesa::query()
            ->leftJoin('desa', 'desa.id_desa', '=', 'pj_desa.id_desa')
            ->leftJoin('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.id_kecamatan')
            ->select('pj_desa.id_pjdesa', 'pj_desa.email', 'pj_desa.status_mobilisasi', 'desa.desa as nama_desa', 'kecamatan.kecamatan as nama_kecamatan')
            ->selectSub(CapaianKegiatan::selectRaw('count(*)')
                ->whereColumn('capaian_kegiatan.email', 'pj_desa.email')
                ->whereYear('bulan', now()->year), 'capaian_count')
            ->when(in_array($mobilisasi, Pjdesa::MOBILISASI_STATUSES, true), fn ($q) => $q->where('pj_desa.status_mobilisasi', $mobilisasi))
            ->with('mahasiswa.sp')
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
            ->addColumn('instansi', fn (Pjdesa $row) => $row->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia')
            ->addColumn('capaian_kpi', fn (Pjdesa $row) => (int) $row->capaian_count)
            ->addColumn('mobilisasi', fn (Pjdesa $row) => view('pjdesa._mobilisasi-badge', ['status' => $row->status_mobilisasi])->render())
            ->filterColumn('kecamatan', fn ($q, $k) => $q->where('kecamatan.kecamatan', 'like', $this->likeContains($k)))
            ->filterColumn('desa', fn ($q, $k) => $q->where('desa.desa', 'like', $this->likeContains($k)))
            ->filterColumn('pjdesa', fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where('nama', 'like', $this->likeContains($k))))
            ->filterColumn('instansi', fn ($q, $k) => $q->whereHas('mahasiswa.sp', fn ($sp) => $sp->where('nm_lemb', 'like', $this->likeContains($k))))
            ->orderColumn('kecamatan', 'kecamatan.kecamatan $1')
            ->orderColumn('desa', 'desa.desa $1')
            ->orderColumn('pjdesa', $mahasiswaCol('m.nama').' $1')
            ->orderColumn('instansi', $mahasiswaCol('sp.nm_lemb').' $1')
            ->addColumn('action', fn (Pjdesa $row) => ActionButtons::make(
                urlDelete: url('pjdesa/destroy'),
                idField: 'id_pjdesa',
                idValue: $row->id_pjdesa,
            ))
            ->rawColumns(['mobilisasi', 'action'])
            ->make(true);
    }

    public function destroy(Request $request)
    {
        $pjdesa = Pjdesa::find($request->input('id_pjdesa'));
        if (! $pjdesa) {
            return $this->notFound();
        }

        if (CapaianKegiatan::where('email', $pjdesa->email)->whereYear('bulan', now()->year)->exists()) {
            return $this->deleteRejected('Tidak dapat dihapus karena ketua kelompok memiliki capaian KPI tahun ini');
        }

        DB::transaction(function () use ($pjdesa) {
            $pjdesa->delete();
            // Revoke only when no other assignment remains for this email
            if (! Pjdesa::where('email', $pjdesa->email)->exists()) {
                User::where('email', $pjdesa->email)->where('akses', 'pjdesa')->update(['akses' => null]);
            }
        });

        return $this->deleted('Akses ketua kelompok berhasil dicabut');
    }
}
