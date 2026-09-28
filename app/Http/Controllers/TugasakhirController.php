<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\TugasakhirRequest;
use App\Models\Tugasakhir;
use Illuminate\Http\Request;

class TugasakhirController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('tugasakhir.index');
    }

    public function listdata(Request $request)
    {
        return view('tugasakhir.listdata', ['data' => Tugasakhir::ownedBy($request->user())->get()]);
    }

    public function tambah()
    {
        return view('tugasakhir.tambah');
    }

    public function insert(TugasakhirRequest $request)
    {
        Tugasakhir::create([
            'email' => $request->user()->email,
            'tautan' => $request->validated('tautan'),
            'tahun' => (int) date('Y'),
        ]);

        return $this->saved();
    }

    public function edit(Request $request, string $id_tugasakhir)
    {
        return view('tugasakhir.edit', ['data' => Tugasakhir::ownedBy($request->user())->findOrFail($id_tugasakhir)]);
    }

    public function update(TugasakhirRequest $request)
    {
        $tugas = Tugasakhir::ownedBy($request->user())->find($request->validated('id_tugasakhir'));
        if (! $tugas) {
            return $this->notFound();
        }

        $tugas->update(['tautan' => $request->validated('tautan'), 'tahun' => (int) date('Y')]);

        return $this->saved('Data berhasil diupdate.');
    }

    public function destroy(Request $request)
    {
        $deleted = Tugasakhir::ownedBy($request->user())->whereKey($request->input('id_tugasakhir'))->delete();

        return $deleted ? $this->deleted() : $this->failed('Data gagal dihapus');
    }
}
