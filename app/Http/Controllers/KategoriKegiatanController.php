<?php

namespace App\Http\Controllers;

use App\Exports\KategoriKegiatanExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\KategoriKegiatanRequest;
use App\Models\KategoriKegiatan;
use App\Models\CapaianKegiatan;
use App\Models\Logkegiatan;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KategoriKegiatanController extends Controller
{
    use RespondsWithJson;

    private const FIELDS = ['nama_kategori'];

    public function index()
    {
        return view('kategorikegiatan.index');
    }

    public function listdata()
    {
        return view('kategorikegiatan.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(KategoriKegiatan::query())
            ->addIndexColumn()
            ->addColumn('action', fn (KategoriKegiatan $row) => ActionButtons::make(
                urlEdit: url('kategori-kegiatan/edit/'.$row->id_kategori),
                urlDelete: url('kategori-kegiatan/destroy'),
                idField: 'id_kategori',
                idValue: $row->id_kategori,
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('kategorikegiatan.tambah');
    }

    public function insert(KategoriKegiatanRequest $request)
    {
        KategoriKegiatan::create($request->safe()->only(self::FIELDS));

        return $this->saved('Aktivitas berhasil disimpan');
    }

    public function edit(string $id_kategori)
    {
        return view('kategorikegiatan.edit', ['data' => KategoriKegiatan::findOrFail($id_kategori)]);
    }

    public function update(KategoriKegiatanRequest $request)
    {
        KategoriKegiatan::findOrFail($request->validated('id_kategori'))->update($request->safe()->only(self::FIELDS));

        return $this->saved('Aktivitas berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $kategori = KategoriKegiatan::find($request->input('id_kategori'));
        if (! $kategori) {
            return $this->notFound();
        }

        if (CapaianKegiatan::where('id_kategori', $kategori->id_kategori)->exists()
            || Logkegiatan::where('id_kategori', $kategori->id_kategori)->exists()) {
            return $this->deleteRejected('Hapus dulu data terkait');
        }

        $kategori->delete();

        return $this->deleted();
    }

    public function export()
    {
        return Excel::download(new KategoriKegiatanExport, 'kategori_kegiatan_'.date('d-m-Y_H-i-s').'.xlsx');
    }
}
