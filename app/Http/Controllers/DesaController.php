<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\DesaRequest;
use App\Models\Desa;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use App\Models\PenguranganSampah;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $query = Desa::query()
            ->leftJoin('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.id_kecamatan')
            ->select('desa.*', 'kecamatan.kecamatan as kecamatan');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            // Restrict search/order to view columns; others are ambiguous after the join.
            ->whitelist(['id_desa', 'kecamatan', 'desa'])
            ->editColumn('kecamatan', fn (Desa $row) => $row->kecamatan ?? '')
            ->filterColumn('kecamatan', fn ($query, $keyword) => $query->where('kecamatan.kecamatan', 'like', "%{$keyword}%"))
            ->orderColumn('kecamatan', 'kecamatan.kecamatan $1')
            ->editColumn('latitude', fn (Desa $row) => $row->latitude !== null ? (float) $row->latitude : null)
            ->editColumn('longitude', fn (Desa $row) => $row->longitude !== null ? (float) $row->longitude : null)
            ->addColumn('action', fn (Desa $row) => ActionButtons::make(
                urlEdit: url('desa/edit/'.$row->id_desa),
                urlDelete: url('desa/destroy'),
                idField: 'id_desa',
                idValue: $row->id_desa,
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
        Desa::create($request->safe()->only('id_kecamatan', 'desa', 'latitude', 'longitude'));

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
            ->update($request->safe()->only('id_kecamatan', 'desa', 'latitude', 'longitude'));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id_desa');
        // Non-string/non-uuid id would make find() return a Collection
        $desa = Str::isUuid($id) ? Desa::find($id) : null;
        if (! $desa) {
            return $this->notFound();
        }

        $used = Desaprofile::where('id_desa', $desa->id_desa)->exists()
            || Pjdesa::where('id_desa', $desa->id_desa)->exists()
            || Mahasiswa_lokasi::where('id_desa', $desa->id_desa)->exists()
            || PenguranganSampah::where('id_desa', $desa->id_desa)->exists();
        if ($used) {
            return $this->deleteRejected('Data gagal dihapus karena terkait dengan data lain');
        }

        $desa->delete();

        return $this->deleted();
    }
}
