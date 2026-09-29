<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\LokasiProgramRequest;
use App\Models\LokasiProgram;
use App\Models\User;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class LokasiprogramController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('lokasiprogram.index');
    }

    public function listdata()
    {
        return view('lokasiprogram.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(LokasiProgram::query())
            ->addIndexColumn()
            ->addColumn('gambar', fn (LokasiProgram $row) => $row->gambar
                ? '<img src="'.e(asset('storage/'.$row->gambar)).'" alt="Gambar" width="100">'
                : 'Tidak ada gambar')
            ->addColumn('action', fn (LokasiProgram $row) => ActionButtons::make(
                urlEdit: url('lokasiprogram/edit/'.$row->id),
                urlDelete: url('lokasiprogram/destroy'),
                idField: 'id',
                idValue: $row->id,
            ))
            ->rawColumns(['action', 'gambar'])
            ->make(true);
    }

    public function tambah()
    {
        return view('lokasiprogram.tambah');
    }

    public function insert(LokasiProgramRequest $request)
    {
        $data = $request->safe()->only('nama_lokasi');
        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('lokasi', 'public');
        }

        LokasiProgram::create($data);

        return $this->saved();
    }

    public function edit(string $id)
    {
        return view('lokasiprogram.edit', ['data' => LokasiProgram::findOrFail($id)]);
    }

    public function update(LokasiProgramRequest $request)
    {
        $lokasi = LokasiProgram::findOrFail($request->validated('id'));
        $data = $request->safe()->only('nama_lokasi');

        if ($request->hasFile('gambar')) {
            if ($lokasi->gambar) {
                Storage::disk('public')->delete($lokasi->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('lokasi', 'public');
        }

        $lokasi->update($data);

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $lokasi = LokasiProgram::find($request->input('id'));
        if (! $lokasi) {
            return $this->notFound();
        }

        if (User::where('location_program', $lokasi->id)->exists()) {
            return $this->deleteRejected('Data gagal dihapus karena masih dipakai pengguna');
        }

        $lokasi->delete();

        return $this->deleted();
    }
}
