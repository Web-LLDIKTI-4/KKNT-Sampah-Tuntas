<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\DesaRequest;
use App\Models\Desa;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DesaController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('desa.index');
    }

    public function listdata()
    {
        return view('desa.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Desa::with('kecamatan')->get())
            ->addIndexColumn()
            ->addColumn('kecamatan', fn (Desa $row) => $row->kecamatan->kecamatan ?? '')
            ->addColumn('action', fn (Desa $row) => ActionButtons::crud(
                url('desa/edit/'.$row->id_desa),
                url('desa/destroy'),
                'id_desa',
                $row->id_desa
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('desa.tambah', ['kecamatan' => Kecamatan::orderBy('kecamatan')->get()]);
    }

    public function insert(DesaRequest $request)
    {
        Desa::create($request->safe()->only('id_kecamatan', 'desa'));

        return $this->saved();
    }

    public function edit(string $id_desa)
    {
        return view('desa.edit', [
            'data' => Desa::findOrFail($id_desa),
            'kecamatan' => Kecamatan::orderBy('kecamatan')->get(),
        ]);
    }

    public function update(DesaRequest $request)
    {
        Desa::findOrFail($request->validated('id_desa'))
            ->update($request->safe()->only('id_kecamatan', 'desa'));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $desa = Desa::find($request->input('id_desa'));
        if (! $desa) {
            return $this->notFound();
        }

        $used = Desaprofile::where('id_desa', $desa->id_desa)->exists()
            || Pjdesa::where('id_desa', $desa->id_desa)->exists()
            || Mahasiswa_lokasi::where('id_desa', $desa->id_desa)->exists();
        if ($used) {
            return $this->deleteRejected('Data gagal dihapus karena terkait dengan data lain');
        }

        $desa->delete();

        return $this->deleted();
    }
}
