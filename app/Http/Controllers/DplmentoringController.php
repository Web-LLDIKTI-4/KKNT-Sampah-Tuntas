<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Dpl\MentoringInsertRequest;
use App\Http\Requests\Dpl\PenilaianLogbulananRequest;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Logbulanan;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Satuanpendidikan;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use App\Support\XlsxStreamer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

class DplmentoringController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index()
    {
        return view('mentoring.index');
    }

    public function listdata()
    {
        return view('mentoring.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        return $this->makeWithTieBreaker($this->table($request)
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => $row->mahasiswa->nim ?? 'tidak ada')
            ->addColumn('nama', fn ($row) => $row->mahasiswa->nama ?? 'tidak ada')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? 'tidak terdata')
            ->addColumn('prodi', fn ($row) => $row->mahasiswa->prodi ?? 'tidak ada')
            ->addColumn('nama_kecamatan', fn ($row) => $row->mahasiswa?->nama_kecamatan)
            ->addColumn('nama_desa', fn ($row) => $row->mahasiswa?->nama_desa)
            ->addColumn('rekapnilai', fn ($row) => ActionButtons::modal(
                url('dplmentoring/rekapnilai/'.base64_encode($row->email_mahasiswa)),
                'Rekap Nilai',
                'Rekap Nilai Log Bulanan',
            ))
            ->addColumn('nilai_freeform', fn ($row) => $row->mahasiswa
                ? '<a href="'.e(url('dplmentoring/nilaifreeform/'.$row->mahasiswa->id_mahasiswa)).'" title="Nilai konversi dan Free form">lihat data</a>'
                : 'tidak ada - '.e($row->email_mahasiswa))
            ->addColumn('tugasakhir', fn ($row) => HtmlSanitizer::link($row->tugasakhir?->tautan) ?: '-')
            ->addColumn('action', fn ($row) => ActionButtons::make(
                urlDelete: url('dplmentoring/destroy/'.$row->id_mentoring),
                idField: 'id_mentoring',
                idValue: $row->id_mentoring,
            ))
            ->rawColumns(['action', 'rekapnilai', 'nilai_freeform', 'tugasakhir']), 'dpl_mentoring.id_mentoring');
    }

    // Same lokasi_status, search & order as the table
    public function export(Request $request)
    {
        abort_unless($request->user()->role === 'dpl', 403);
        $this->sanitizeDataTablesInput();
        $rows = $this->dataTablesExportQuery($this->table($request), 'dpl_mentoring.id_mentoring')
            // Same source as rekapnilai (all log bulanan of the mentee); only valid grades counted
            ->addSelect(['avg_nilai_bulanan' => Logbulanan::selectRaw('avg(nilai)')
                ->whereColumn('logkegiatan_bulanan.email', 'dpl_mentoring.email_mahasiswa')
                ->whereIn('nilai', PenilaianLogbulananRequest::PILIHAN_NILAI)])
            ->lazy(1000);
        $status = ['belum' => 'Belum set', 'sudah' => 'Sudah set'][$request->query('lokasi_status')] ?? null;

        return (new XlsxStreamer('Data Mahasiswa Bimbingan', [
            XlsxStreamer::column('NIM', 'mahasiswa.nim', 15),
            XlsxStreamer::column('Nama', 'mahasiswa.nama', 30),
            XlsxStreamer::column('Perguruan Tinggi', 'mahasiswa.sp.nm_lemb', 35),
            XlsxStreamer::column('Prodi', 'mahasiswa.prodi', 25),
            XlsxStreamer::column('Kecamatan', 'mahasiswa.nama_kecamatan', 20),
            XlsxStreamer::column('Kelurahan/Desa', 'mahasiswa.nama_desa', 20),
            XlsxStreamer::column('Rata-rata Nilai Log Bulanan', fn ($row) => $row->avg_nilai_bulanan === null ? null : round((float) $row->avg_nilai_bulanan, 2), 15),
            // External link submitted by the student (no stored file); same value as the table link
            XlsxStreamer::column('Tugas Akhir', fn ($row) => HtmlSanitizer::safeUrl($row->tugasakhir?->tautan), 45),
        ]))
            ->info(['Status Lokasi' => $status, 'Kata kunci' => $request->input('search.value')])
            ->download('data_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx', $rows);
    }

    // Shared by list and export: same DPL scope, filter, search & order; call after sanitizeDataTablesInput()
    private function table(Request $request): EloquentDataTable
    {
        $status = $request->query('lokasi_status');
        // HTML columns (rekapnilai, tugasakhir, action) stay out of search/order
        $whitelist = ['nim', 'nama', 'nm_lemb', 'prodi', ...array_keys(Mahasiswa::LOKASI_COLUMNS)];
        $query = Dplmentoring::ofDpl($request->user())
            // Only a known status narrows; otherwise mentees without a mahasiswa record stay listed
            ->when(in_array($status, Mahasiswa::LOKASI_STATUSES, true), fn ($q) => $q->whereHas('mahasiswa', fn ($m) => $m->lokasiStatus($status)))
            ->with(['mahasiswa' => fn ($q) => $q->withLatestLokasi()->orderByDesc('mahasiswa.id_mahasiswa'), 'mahasiswa.sp',
                // Latest tugas akhir only, so table & export show the same row
                'tugasakhir' => fn ($q) => $q->without(['mahasiswa', 'dplmentoring'])->latest('tugasakhir.created_at')->orderByDesc('tugasakhir.id_tugasakhir'),
            ])
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->oldest('dpl_mentoring.created_at'));

        $dataTable = DataTables::eloquent($query)->whitelist($whitelist)
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->whereHas('mahasiswa.sp', fn ($sp) => $sp->where('nm_lemb', 'like', $this->likeContains($k))))
            ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy($this->menteeValue(
                Satuanpendidikan::select('nm_lemb')->whereColumn('npsn', 'mahasiswa.kodept')->orderBy('npsn')->limit(1)
            ), $order));
        // Relation-derived columns: search via whereHas, sort via correlated subquery
        $columns = ['nim' => 'mahasiswa.nim', 'nama' => 'mahasiswa.nama', 'prodi' => 'mahasiswa.prodi']
            + array_map(Mahasiswa::latestLokasi(...), Mahasiswa::LOKASI_COLUMNS);
        foreach ($columns as $alias => $column) {
            $dataTable
                ->filterColumn($alias, fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where($column, 'like', $this->likeContains($k))))
                ->orderColumn($alias, fn ($q, $order) => $q->orderBy($this->menteeValue($column), $order));
        }

        return $dataTable;
    }

    // Single deterministic mahasiswa value per mentoring row (email is not unique in mahasiswa)
    private function menteeValue(string|QueryBuilder|EloquentBuilder $column): EloquentBuilder
    {
        return Mahasiswa::query()
            ->select(['value' => $column])
            ->whereColumn('mahasiswa.email', 'dpl_mentoring.email_mahasiswa')
            ->orderByDesc('mahasiswa.id_mahasiswa')
            ->limit(1);
    }

    public function tambah(Request $request)
    {
        abort_unless($request->user()->role === 'dpl', 403);
        $kodept = $request->user()->dpl?->kodept;
        $data = $kodept
            ? Mahasiswa::with(['sp', 'locationProgram'])->where('kodept', $kodept)->whereDoesntHave('dplmentoring')->get()
            : collect();

        return view('mentoring.tambah', ['data' => $data, 'kodeptMissing' => ! $kodept]);
    }

    public function insert(MentoringInsertRequest $request)
    {
        $emails = $request->validated('createuser');

        // Hanya mahasiswa terdaftar sekampus dengan DPL yang belum punya DPL
        $kodept = $request->user()->dpl?->kodept;
        if (! $kodept) {
            return response()->json(['error' => 'Lengkapi kampus di profil DPL terlebih dahulu']);
        }

        $eligible = Mahasiswa::whereIn('email', $emails)
            ->where('kodept', $kodept)
            ->whereDoesntHave('dplmentoring')
            ->pluck('email');

        // Single atomic insert; duplicates skipped via unique email_mahasiswa
        $now = now();
        $rows = $eligible->map(fn ($email) => [
            'id_mentoring' => (string) Str::uuid(),
            'email_mahasiswa' => $email,
            'email_dpl' => $request->user()->email,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        $inserted = $rows ? Dplmentoring::insertOrIgnore($rows) : 0;

        return response()->json(['success' => $inserted.' mahasiswa berhasil ditambahkan']);
    }

    public function destroy(Request $request, string $id_mentoring)
    {
        $mentoring = Dplmentoring::ofDpl($request->user())->find($id_mentoring);
        if (! $mentoring) {
            return $this->notFound();
        }

        $mentoring->delete();

        return $this->deleted();
    }

    public function rekapnilai(Request $request, string $email)
    {
        $emailMahasiswa = base64_decode($email, true) ?: null;
        abort_unless(Dplmentoring::isMentor($request->user(), $emailMahasiswa), 404);

        return view('mentoring.rekapnilai', [
            'groupedData' => Logbulanan::where('email', $emailMahasiswa)->get()->groupBy('bulan'),
            'bulan' => self::BULAN,
        ]);
    }

    public function nilaifreeform(Request $request, string $id_mahasiswa)
    {
        $mahasiswa = $this->menteeOrFail($request, $id_mahasiswa);

        return view('mentoring.nilaifreeform', ['mahasiswa' => $mahasiswa, 'id_mahasiswa' => $id_mahasiswa]);
    }

    public function nilaikonversi(Request $request, string $id_mahasiswa)
    {
        $mahasiswa = $this->menteeOrFail($request, $id_mahasiswa);

        return view('mentoring.nilaifreeform_nilaikonversi', [
            'mahasiswa' => $mahasiswa,
            'nilai' => Nilaikonversi::where('id_mahasiswa', $id_mahasiswa)->get(),
        ]);
    }

    public function freeform(Request $request, string $id_mahasiswa)
    {
        $mahasiswa = $this->menteeOrFail($request, $id_mahasiswa);

        return view('mentoring.nilaifreeform_freeform', [
            'mahasiswa' => $mahasiswa,
            'nilai' => Freeform::where('id_mahasiswa', $id_mahasiswa)->get(),
        ]);
    }

    private function menteeOrFail(Request $request, string $idMahasiswa): Mahasiswa
    {
        $mahasiswa = Mahasiswa::findOrFail($idMahasiswa);
        abort_unless(Dplmentoring::isMentor($request->user(), $mahasiswa->email), 404);

        return $mahasiswa;
    }
}
