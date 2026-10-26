<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\KecamatanRequest;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Picdlh;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KecamatanController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

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
        $this->sanitizeDataTablesInput();
        $whitelist = ['id_kecamatan', 'kecamatan'];

        $query = Kecamatan::query()
            // Default order only when the client sends none
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('kecamatan'));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', fn (Kecamatan $row) => ActionButtons::make(
                urlEdit: url('kecamatan/edit/'.$row->id_kecamatan),
                urlDelete: url('kecamatan/destroy'),
                idField: 'id_kecamatan',
                idValue: $row->id_kecamatan,
            ))
            ->rawColumns(['action'])
            ->whitelist($whitelist)
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
        $id = $request->input('id_kecamatan');
        // Array input would make find() return a Collection; legacy ids may be non-UUID
        $kecamatan = is_string($id) && $id !== '' ? Kecamatan::find($id) : null;
        if (! $kecamatan) {
            return $this->notFound();
        }

        if (Desa::where('id_kecamatan', $kecamatan->id_kecamatan)->exists()
            || Picdlh::where('id_kecamatan', $kecamatan->id_kecamatan)->exists()) {
            return $this->deleteRejected('Data gagal dihapus karena terkait dengan data lain');
        }

        $kecamatan->delete();

        return $this->deleted();
    }
}
