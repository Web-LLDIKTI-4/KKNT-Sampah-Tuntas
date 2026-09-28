<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\KecamatanRequest;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KecamatanController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('kecamatan.index');
    }

    public function listdata()
    {
        return view('kecamatan.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Kecamatan::query())
            ->addIndexColumn()
            ->addColumn('action', fn (Kecamatan $row) => ActionButtons::crud(
                url('kecamatan/edit/'.$row->id_kecamatan),
                url('kecamatan/destroy'),
                'id_kecamatan',
                $row->id_kecamatan
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('kecamatan.tambah');
    }

    public function insert(KecamatanRequest $request)
    {
        Kecamatan::create($request->safe()->only('kecamatan'));

        return $this->saved();
    }

    public function edit(string $id_kecamatan)
    {
        return view('kecamatan.edit', ['data' => Kecamatan::findOrFail($id_kecamatan)]);
    }

    public function update(KecamatanRequest $request)
    {
        Kecamatan::findOrFail($request->validated('id_kecamatan'))
            ->update($request->safe()->only('kecamatan'));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $kecamatan = Kecamatan::find($request->input('id_kecamatan'));
        if (! $kecamatan) {
            return $this->notFound();
        }

        if (Desa::where('id_kecamatan', $kecamatan->id_kecamatan)->exists()) {
            return $this->deleteRejected('Data gagal dihapus karena terkait dengan data lain');
        }

        $kecamatan->delete();

        return $this->deleted();
    }
}
