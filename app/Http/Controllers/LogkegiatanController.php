<?php

namespace App\Http\Controllers;

use App\Exports\LogHarianExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\LogkegiatanRequest;
use App\Models\Kehadiran;
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

        // Join kpi agar nama_kpi bisa dicari & diurutkan di SQL
        $query = Logkegiatan::ownedBy($request->user())
            ->without(['kpi', 'mahasiswa', 'dplmentoring'])
            ->select('logkegiatan.id_log', 'logkegiatan.tanggal', 'logkegiatan.deskripsi', 'logkegiatan.tautan',
                'logkegiatan.volume', 'logkegiatan.satuan', 'kpi.nama_kpi')
            ->leftJoin('kpi', 'kpi.id_kpi', '=', 'logkegiatan.id_kpi');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('nama_kpi', fn (Logkegiatan $row) => $row->nama_kpi ?? '')
            ->filterColumn('nama_kpi', fn ($q, $keyword) => $q->where('kpi.nama_kpi', 'like', "%{$keyword}%"))
            ->orderColumn('nama_kpi', 'kpi.nama_kpi $1')
            ->editColumn('deskripsi', fn (Logkegiatan $row) => HtmlSanitizer::clean($row->deskripsi).' '.HtmlSanitizer::link($row->tautan))
            ->addColumn('action', fn (Logkegiatan $row) => ActionButtons::make(
                urlEdit: url('logkegiatan/edit/'.$row->id_log),
                urlDelete: url('logkegiatan/destroy'),
                idField: 'id_log',
                idValue: $row->id_log,
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
