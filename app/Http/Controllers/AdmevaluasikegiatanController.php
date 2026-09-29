<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Evaluasi\PertanyaanRequest;
use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Satuanpendidikan;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdmevaluasikegiatanController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('evaluasikegiatan.index');
    }

    public function hasilevaluasi()
    {
        return view('evaluasikegiatan.hasilevaluasi');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Evaluasikegiatanjawaban::with('evaluasikegiatan')->get();
        $namaPt = Satuanpendidikan::whereIn('npsn', $data->pluck('kodept')->filter()->unique())->pluck('nm_lemb', 'npsn');

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('kodept', fn ($row) => $row->kodept ?? 'Tidak ada')
            ->addColumn('nm_lemb', fn ($row) => $namaPt[$row->kodept] ?? 'Tidak ada')
            ->addColumn('pertanyaan', fn ($row) => HtmlSanitizer::clean($row->evaluasikegiatan->pertanyaan ?? 'Tidak ada'))
            ->rawColumns(['pertanyaan'])
            ->make(true);
    }

    public function pertanyaanevaluasi()
    {
        return view('evaluasikegiatan.pertanyaanevaluasi');
    }

    public function pertanyaanevaluasilistdata()
    {
        return view('evaluasikegiatan.pertanyaanevaluasi_listdata');
    }

    public function pertanyaanevaluasiserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Evaluasikegiatan::query())
            ->addIndexColumn()
            ->editColumn('pertanyaan', fn (Evaluasikegiatan $row) => HtmlSanitizer::clean($row->pertanyaan) ?? 'Tidak ada')
            ->addColumn('action', fn (Evaluasikegiatan $row) => ActionButtons::make(
                urlEdit: url('admevaluasikegiatan/edit/'.$row->id_evaluasi),
                urlDelete: route('admevaluasikegiatan.pertanyaanevaluasi.destroy'),
                idField: 'id_evaluasi',
                idValue: $row->id_evaluasi,
            ))
            ->rawColumns(['pertanyaan', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('evaluasikegiatan.pertanyaanevaluasi_tambah');
    }

    public function insert(PertanyaanRequest $request)
    {
        Evaluasikegiatan::create($request->safe()->only('pertanyaan') + ['tahun' => (int) date('Y')]);

        return $this->saved();
    }

    public function edit(string $id_evaluasi)
    {
        return view('evaluasikegiatan.pertanyaanevaluasi_edit', ['data' => Evaluasikegiatan::findOrFail($id_evaluasi)]);
    }

    public function update(PertanyaanRequest $request)
    {
        Evaluasikegiatan::findOrFail($request->validated('id_evaluasi'))
            ->update($request->safe()->only('pertanyaan') + ['tahun' => (int) date('Y')]);

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $evaluasi = Evaluasikegiatan::find($request->input('id_evaluasi'));
        if (! $evaluasi) {
            return $this->deleteRejected('Data gagal dihapus, data tersebut tidak ada atau sudah terhapus!');
        }

        if (Evaluasikegiatanjawaban::where('id_evaluasi', $evaluasi->id_evaluasi)->exists()) {
            return $this->deleteRejected('Pertanyaan tidak dapat dihapus karena sudah dijawab');
        }

        $evaluasi->delete();

        return $this->deleted();
    }
}
