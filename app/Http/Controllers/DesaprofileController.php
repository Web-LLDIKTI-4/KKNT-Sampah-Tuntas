<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\DesaprofileRequest;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
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

        $query = Desaprofile::query()
            ->select('desa_profile.*', 'desa.desa as nama_desa')
            ->leftJoin('desa', 'desa.id_desa', '=', 'desa_profile.id_desa');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('desa', fn (Desaprofile $row) => $row->nama_desa ?? '')
            ->filterColumn('desa', fn ($q, $keyword) => $q->where('desa.desa', 'like', "%{$keyword}%"))
            ->orderColumn('desa', 'desa.desa $1')
            ->editColumn('potensi', fn (Desaprofile $row) => HtmlSanitizer::clean($row->potensi))
            ->editColumn('masalah', fn (Desaprofile $row) => HtmlSanitizer::clean($row->masalah))
            ->addColumn('action', fn (Desaprofile $row) => ActionButtons::make(
                urlEdit: url('desaprofile/edit/'.$row->id_profile),
                urlDelete: url('desaprofile/destroy'),
                idField: 'id_profile',
                idValue: $row->id_profile,
            ))
            ->rawColumns(['action', 'potensi', 'masalah'])
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
