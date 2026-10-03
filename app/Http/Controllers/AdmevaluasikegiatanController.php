<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Evaluasi\PertanyaanRequest;
use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
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

        // Join agar nm_lemb/pertanyaan bisa dicari & diurutkan di SQL
        $query = Evaluasikegiatanjawaban::query()
            ->select('evaluasi_kegiatan_jawaban.*', 'ref_satuanpendidikan.nm_lemb', 'evaluasi_kegiatan.pertanyaan')
            ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'evaluasi_kegiatan_jawaban.kodept')
            ->leftJoin('evaluasi_kegiatan', 'evaluasi_kegiatan.id_evaluasi', '=', 'evaluasi_kegiatan_jawaban.id_evaluasi');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('kodept', fn ($row) => $row->kodept ?? 'Tidak ada')
            ->editColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Tidak ada')
            ->editColumn('pertanyaan', fn ($row) => HtmlSanitizer::clean($row->pertanyaan ?? 'Tidak ada'))
            ->filterColumn('nm_lemb', fn ($q, $keyword) => $q->where('ref_satuanpendidikan.nm_lemb', 'like', "%{$keyword}%"))
            ->filterColumn('pertanyaan', fn ($q, $keyword) => $q->where('evaluasi_kegiatan.pertanyaan', 'like', "%{$keyword}%"))
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
