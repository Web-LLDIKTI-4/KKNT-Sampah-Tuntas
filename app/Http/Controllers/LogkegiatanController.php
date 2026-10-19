<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\LogHarianRequest;
use App\Http\Requests\Mahasiswa\LogkegiatanRequest;
use App\Models\Kehadiran;
use App\Models\KategoriKegiatan;
use App\Models\Logkegiatan;
use App\Services\LogHarianService;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogkegiatanController extends Controller
{
    use HandlesDataTablesOrder;
    use RespondsWithJson;

    public function index(Request $request)
    {
        return view('logkegiatan.mahasiswa.index', [
            'kehadiran' => Kehadiran::where('email', $request->user()->email)->whereDate('tanggal', today())->first(),
        ]);
    }

    public function listdata()
    {
        return view('logkegiatan.mahasiswa.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $data = Logkegiatan::ownedBy($request->user())
            ->whereNotNull('deskripsi')
            ->without(['mahasiswa', 'dplmentoring'])
            ->with('kategoriKegiatan')
            ->orderByDesc('tanggal')
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nama_kategori', fn (Logkegiatan $row) => $row->kategoriKegiatan->nama_kategori ?? '')
            ->editColumn('deskripsi', fn (Logkegiatan $row) => HtmlSanitizer::clean($row->deskripsi))
            ->addColumn('tautan', fn (Logkegiatan $row) => HtmlSanitizer::link($row->tautan, 'Lihat bukti'))
            ->addColumn('action', fn (Logkegiatan $row) => ActionButtons::make(
                urlEdit: url('logkegiatan/edit/'.$row->id_log),
                urlDelete: url('logkegiatan/destroy'),
                idField: 'id_log',
                idValue: $row->id_log,
            ))
            ->rawColumns(['deskripsi', 'tautan', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('logkegiatan.mahasiswa.tambah', ['kategoriKegiatan' => KategoriKegiatan::orderBy('nama_kategori')->get()]);
    }

    public function insert(LogHarianRequest $request, LogHarianService $logHarian)
    {
        $logHarian->store($request->validated(), $request->user()->email);

        return $this->saved('Log harian berhasil disimpan');
    }

    public function edit(Request $request, string $id_log)
    {
        return view('logkegiatan.mahasiswa.edit', [
            'data' => Logkegiatan::ownedBy($request->user())->whereNotNull('deskripsi')->findOrFail($id_log),
            'kategoriKegiatan' => KategoriKegiatan::orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(LogkegiatanRequest $request)
    {
        $log = Logkegiatan::ownedBy($request->user())->whereNotNull('deskripsi')->find($request->validated('id_log'));
        if (! $log) {
            return $this->notFound();
        }

        $log->update($request->safe()->only(LogkegiatanRequest::FIELDS));

        return $this->saved('Log harian berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $log = Logkegiatan::ownedBy($request->user())->whereNotNull('deskripsi')->find($request->input('id_log'));
        if (! $log) {
            return $this->notFound();
        }

        $log->delete();

        return $this->deleted();
    }
}
