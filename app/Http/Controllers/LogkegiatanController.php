<?php

namespace App\Http\Controllers;

use App\Exports\LogHarianExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\LogkegiatanRequest;
use App\Models\Kpi;
use App\Models\Logkegiatan;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class LogkegiatanController extends Controller
{
    use RespondsWithJson;

    private const FIELDS = ['tanggal', 'deskripsi', 'volume', 'satuan', 'id_kpi', 'tautan'];

    public function index()
    {
        return view('logkegiatan.mahasiswa.index');
    }

    public function listdata()
    {
        return view('logkegiatan.mahasiswa.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Logkegiatan::ownedBy($request->user())->with('kpi')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nama_kpi', fn (Logkegiatan $row) => $row->kpi->nama_kpi ?? '')
            ->editColumn('deskripsi', fn (Logkegiatan $row) => HtmlSanitizer::clean($row->deskripsi).' '.HtmlSanitizer::link($row->tautan))
            ->addColumn('action', fn (Logkegiatan $row) => ActionButtons::crud(
                url('logkegiatan/edit/'.$row->id_log),
                url('logkegiatan/destroy'),
                'id_log',
                $row->id_log
            ))
            ->rawColumns(['deskripsi', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('logkegiatan.mahasiswa.tambah', ['kpi' => Kpi::orderBy('nama_kpi')->get()]);
    }

    public function insert(LogkegiatanRequest $request)
    {
        Logkegiatan::create($request->safe()->only(self::FIELDS) + ['email' => $request->user()->email]);

        return $this->saved('Log kegiatan berhasil disimpan');
    }

    public function edit(Request $request, string $id_log)
    {
        return view('logkegiatan.mahasiswa.edit', [
            'data' => Logkegiatan::ownedBy($request->user())->findOrFail($id_log),
            'kpi' => Kpi::orderBy('nama_kpi')->get(),
        ]);
    }

    public function update(LogkegiatanRequest $request)
    {
        $log = Logkegiatan::ownedBy($request->user())->find($request->validated('id_log'));
        if (! $log) {
            return $this->notFound();
        }

        $log->update($request->safe()->only(self::FIELDS));

        return $this->saved('Log Kegiatan berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $log = Logkegiatan::ownedBy($request->user())->find($request->input('id_log'));
        if (! $log) {
            return $this->notFound();
        }

        $log->delete();

        return $this->deleted();
    }

    public function export(Request $request)
    {
        return Excel::download(new LogHarianExport($request->user()->email), 'log_harian-'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
