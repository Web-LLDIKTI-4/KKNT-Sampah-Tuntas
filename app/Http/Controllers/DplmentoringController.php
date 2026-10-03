<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Dpl\MentoringBulkRequest;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Logbulanan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Satuanpendidikan;
use App\Support\ActionButtons;
use App\Support\BulkSelectDataTable;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DplmentoringController extends Controller
{
    use RespondsWithJson;

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

        $data = Dplmentoring::ofDpl($request->user())->with(['mahasiswa.sp', 'tugasakhir'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => $row->mahasiswa->nim ?? 'tidak ada')
            ->addColumn('nama', fn ($row) => $row->mahasiswa->nama ?? 'tidak ada')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? 'tidak terdata')
            ->addColumn('prodi', fn ($row) => $row->mahasiswa->prodi ?? 'tidak ada')
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
            ->rawColumns(['action', 'rekapnilai', 'nilai_freeform', 'tugasakhir'])
            ->make(true);
    }

    public function tambah()
    {
        return view('mentoring.tambah', [
            'ptOptions' => Satuanpendidikan::orderBy('nm_lemb')->pluck('nm_lemb', 'npsn'),
            'lokasiOptions' => LokasiProgram::orderBy('nama_lokasi')->pluck('nama_lokasi', 'id'),
        ]);
    }

    // Aturan bisnis tetap: semua mahasiswa tanpa pembimbing (lintas PT/lokasi), dipersempit filter wajib
    public function tambahserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        abort_unless($request->user()->role === 'dpl', 403);

        return BulkSelectDataTable::make(Mahasiswa::whereDoesntHave('dplmentoring'), ['nim', 'nama', 'email'], $request)->make(true);
    }

    public function insert(MentoringBulkRequest $request)
    {
        // Hanya mahasiswa terdaftar yang belum punya DPL
        $eligible = Mahasiswa::whereIn('email', $request->emails())
            ->whereDoesntHave('dplmentoring')
            ->pluck('email');

        foreach ($eligible as $email) {
            Dplmentoring::create(['email_mahasiswa' => $email, 'email_dpl' => $request->user()->email]);
        }

        return response()->json(['success' => $eligible->count().' mahasiswa berhasil ditambahkan']);
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
