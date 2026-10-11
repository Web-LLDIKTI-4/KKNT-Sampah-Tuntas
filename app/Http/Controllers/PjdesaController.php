<?php

namespace App\Http\Controllers;

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
    use RespondsWithJson;

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

        $data = Pjdesa::with(['desa.kecamatan', 'mahasiswa.sp'])->get();
        // Current-year capaian count per email, single grouped query
        $capaian = CapaianKegiatan::whereYear('bulan', now()->year)
            ->whereIn('email', $data->pluck('email')->filter()->unique())
            ->groupBy('email')
            ->pluck(DB::raw('count(*)'), 'email');

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('kecamatan', fn (Pjdesa $row) => $row->desa->kecamatan->kecamatan ?? '')
            ->addColumn('desa', fn (Pjdesa $row) => $row->desa->desa ?? '')
            ->addColumn('pjdesa', fn (Pjdesa $row) => $row->mahasiswa->nama ?? 'Data tidak tersedia')
            ->addColumn('instansi', fn (Pjdesa $row) => $row->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia')
            ->addColumn('capaian_kpi', fn (Pjdesa $row) => (int) ($capaian[$row->email] ?? 0))
            ->addColumn('action', fn (Pjdesa $row) => ActionButtons::make(
                urlDelete: url('pjdesa/destroy'),
                idField: 'id_pjdesa',
                idValue: $row->id_pjdesa,
            ))
            ->rawColumns(['action'])
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
