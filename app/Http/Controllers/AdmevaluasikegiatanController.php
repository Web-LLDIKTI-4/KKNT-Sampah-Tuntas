<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
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
    use HandlesDataTablesOrder;
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

        $this->sanitizeDataTablesInput();

        $whitelist = ['kodept', 'nm_lemb', 'pertanyaan', 'jawaban'];
        // npsn is unique, so the join never duplicates answers
        $query = Evaluasikegiatanjawaban::query()
            ->select('evaluasi_kegiatan_jawaban.*', 'sp.nm_lemb')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'evaluasi_kegiatan_jawaban.kodept')
            ->with('evaluasikegiatan:id_evaluasi,pertanyaan')
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('evaluasi_kegiatan_jawaban.id'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->addColumn('kodept', fn ($row) => $row->kodept ?? 'Tidak ada')
            ->addColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Tidak ada')
            ->filterColumn('kodept', fn ($q, $k) => $q->where('evaluasi_kegiatan_jawaban.kodept', 'like', $this->likeContains($k)))
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->where('sp.nm_lemb', 'like', $this->likeContains($k)))
            ->filterColumn('pertanyaan', fn ($q, $k) => $this->whereRelatedLike($q, 'evaluasikegiatan', 'pertanyaan', $k))
            ->filterColumn('jawaban', fn ($q, $k) => $q->where('evaluasi_kegiatan_jawaban.jawaban', 'like', $this->likeContains($k)))
            ->orderColumn('kodept', 'evaluasi_kegiatan_jawaban.kodept $1')
            ->orderColumn('nm_lemb', 'sp.nm_lemb $1')
            // id_evaluasi is the primary key, so the subquery returns at most one row
            ->orderColumn('pertanyaan', fn ($q, $order) => $q->orderBy(
                Evaluasikegiatan::select('pertanyaan')->whereColumn('evaluasi_kegiatan.id_evaluasi', 'evaluasi_kegiatan_jawaban.id_evaluasi'),
                $order
            ))
            ->orderColumn('jawaban', 'evaluasi_kegiatan_jawaban.jawaban $1')
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
