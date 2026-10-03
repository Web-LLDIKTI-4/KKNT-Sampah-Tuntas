<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\PjdesaRequest;
use App\Models\Kecamatan;
use App\Models\Kpicapaian;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
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

        // desa/kecamatan lewat join (PK); mahasiswa lewat email (tidak unik) tetap eager load
        $query = Pjdesa::query()
            ->select('pj_desa.*', 'desa.desa as nama_desa', 'kecamatan.kecamatan as nama_kecamatan')
            ->leftJoin('desa', 'desa.id_desa', '=', 'pj_desa.id_desa')
            ->leftJoin('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.id_kecamatan')
            ->with(['mahasiswa:email,nama,kodept', 'mahasiswa.sp:npsn,nm_lemb']);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('kecamatan', fn (Pjdesa $row) => $row->nama_kecamatan ?? '')
            ->addColumn('desa', fn (Pjdesa $row) => $row->nama_desa ?? '')
            ->addColumn('pjdesa', fn (Pjdesa $row) => $row->mahasiswa->nama ?? 'Data tidak tersedia')
            ->addColumn('instansi', fn (Pjdesa $row) => $row->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia')
            ->filterColumn('kecamatan', fn ($q, $keyword) => $q->where('kecamatan.kecamatan', 'like', "%{$keyword}%"))
            ->filterColumn('desa', fn ($q, $keyword) => $q->where('desa.desa', 'like', "%{$keyword}%"))
            ->filterColumn('pjdesa', fn ($q, $keyword) => $q->whereIn('pj_desa.email',
                Mahasiswa::select('email')->where('nama', 'like', "%{$keyword}%")))
            ->filterColumn('instansi', fn ($q, $keyword) => $q->whereIn('pj_desa.email', Mahasiswa::select('email')
                ->whereIn('kodept', Satuanpendidikan::where('nm_lemb', 'like', "%{$keyword}%")->pluck('npsn')->all())))
            ->orderColumn('kecamatan', 'kecamatan.kecamatan $1')
            ->orderColumn('desa', 'desa.desa $1')
            ->orderColumn('pjdesa', '(SELECT nama FROM mahasiswa WHERE mahasiswa.email = pj_desa.email LIMIT 1) $1')
            ->orderColumn('instansi', '(SELECT sp.nm_lemb FROM mahasiswa m JOIN ref_satuanpendidikan sp ON sp.npsn = m.kodept WHERE m.email = pj_desa.email LIMIT 1) $1')
            ->addColumn('action', fn (Pjdesa $row) => ActionButtons::make(
                urlEdit: url('pjdesa/edit/'.$row->id_pjdesa),
                urlDelete: url('pjdesa/destroy'),
                idField: 'id_pjdesa',
                idValue: $row->id_pjdesa,
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('pjdesa.tambah', $this->formData());
    }

    public function insert(PjdesaRequest $request)
    {
        Pjdesa::create($request->safe()->only('id_desa', 'email'));

        return $this->saved();
    }

    public function edit(string $id_pjdesa)
    {
        return view('pjdesa.edit', ['data' => Pjdesa::findOrFail($id_pjdesa)] + $this->formData());
    }

    public function update(PjdesaRequest $request)
    {
        Pjdesa::findOrFail($request->validated('id_pjdesa'))
            ->update($request->safe()->only('id_desa', 'email'));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $pjdesa = Pjdesa::find($request->input('id_pjdesa'));
        if (! $pjdesa) {
            return $this->notFound();
        }

        if (Kpicapaian::where('email', $pjdesa->email)->exists()) {
            return $this->deleteRejected('Data tidak dapat di hapus karena terkait dengan data capaian');
        }

        $pjdesa->delete();

        return $this->deleted();
    }

    private function formData(): array
    {
        return [
            'kecamatan' => Kecamatan::with(['desa' => fn ($q) => $q->orderBy('desa')])->orderBy('kecamatan')->get(),
            'user' => User::with('mahasiswa.sp')->where('akses', 'pjdesa')->orderBy('name')->get(),
        ];
    }
}
