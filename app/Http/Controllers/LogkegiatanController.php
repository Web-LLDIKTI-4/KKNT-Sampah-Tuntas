<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\LogkegiatanRequest;
use App\Models\Kehadiran;
use App\Models\Kpi;
use App\Models\Logkegiatan;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogkegiatanController extends Controller
{
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

        $data = Logkegiatan::ownedBy($request->user())
            ->whereNotNull('deskripsi')
            ->without(['mahasiswa', 'dplmentoring'])
            ->with('kpi')
            ->orderByDesc('tanggal')
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nama_kpi', fn (Logkegiatan $row) => $row->kpi->nama_kpi ?? '')
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
        return view('logkegiatan.mahasiswa.tambah', ['kpi' => Kpi::orderBy('nama_kpi')->get()]);
    }

    public function insert(LogkegiatanRequest $request)
    {
        Logkegiatan::create($request->safe()->only(LogkegiatanRequest::FIELDS) + ['email' => $request->user()->email]);

        return $this->saved('Log harian berhasil disimpan');
    }

    public function edit(Request $request, string $id_log)
    {
        return view('logkegiatan.mahasiswa.edit', [
            'data' => Logkegiatan::ownedBy($request->user())->whereNotNull('deskripsi')->findOrFail($id_log),
            'kpi' => Kpi::orderBy('nama_kpi')->get(),
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
