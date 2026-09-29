<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\PjdesaRequest;
use App\Models\Kecamatan;
use App\Models\Kpicapaian;
use App\Models\Pjdesa;
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

        $data = Pjdesa::with(['desa.kecamatan', 'mahasiswa.sp'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('kecamatan', fn (Pjdesa $row) => $row->desa->kecamatan->kecamatan ?? '')
            ->addColumn('desa', fn (Pjdesa $row) => $row->desa->desa ?? '')
            ->addColumn('pjdesa', fn (Pjdesa $row) => $row->mahasiswa->nama ?? 'Data tidak tersedia')
            ->addColumn('instansi', fn (Pjdesa $row) => $row->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia')
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
