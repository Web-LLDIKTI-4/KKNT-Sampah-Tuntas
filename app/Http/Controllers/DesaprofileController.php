<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\DesaprofileRequest;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DesaprofileController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('desaprofile.index');
    }

    public function listdata()
    {
        return view('desaprofile.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Desaprofile::with('desa')->get())
            ->addIndexColumn()
            ->addColumn('desa', fn (Desaprofile $row) => $row->desa->desa ?? '')
            ->addColumn('action', fn (Desaprofile $row) => ActionButtons::crud(
                url('desaprofile/edit/'.$row->id_profile),
                url('desaprofile/destroy'),
                'id_profile',
                $row->id_profile
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('desaprofile.tambah', ['kecamatan' => $this->kecamatanWithDesa()]);
    }

    public function insert(DesaprofileRequest $request)
    {
        Desaprofile::create($request->safe()->only('id_desa', 'tahun', 'potensi', 'masalah'));

        return $this->saved();
    }

    public function edit(string $id_profile)
    {
        return view('desaprofile.edit', [
            'data' => Desaprofile::findOrFail($id_profile),
            'kecamatan' => $this->kecamatanWithDesa(),
        ]);
    }

    public function update(DesaprofileRequest $request)
    {
        Desaprofile::findOrFail($request->validated('id_profile'))
            ->update($request->safe()->only('id_desa', 'tahun', 'potensi', 'masalah'));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $profile = Desaprofile::find($request->input('id_profile'));
        if (! $profile) {
            return $this->notFound();
        }

        $profile->delete();

        return $this->deleted();
    }

    private function kecamatanWithDesa()
    {
        return Kecamatan::with(['desa' => fn ($q) => $q->orderBy('desa')])->orderBy('kecamatan')->get();
    }
}
