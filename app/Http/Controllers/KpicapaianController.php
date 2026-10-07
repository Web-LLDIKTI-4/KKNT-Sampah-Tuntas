<?php

namespace App\Http\Controllers;

use App\Exports\CapaiankpiExport;
use App\Exports\Sheets\CapaianKpiPeriodeSheet;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\KpicapaianRequest;
use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Pjdesa;
use App\Models\User;
use App\Services\KpiSampahService;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KpicapaianController extends Controller
{
    use RespondsWithJson;

    private const FIELDS = ['id_kpi', 'status_capaian', 'tautan', 'permasalahan', 'solusi', 'kendala'];

    // Read-only: rekap sampah desa mahasiswa dari pendataan pemilahan (semua bulan)
    public function index(Request $request, KpiSampahService $sampah)
    {
        $idDesa = $sampah->desaMahasiswa($request->user()->email);
        $filter = ['id_desa' => $idDesa];

        return view('kpicapaian.index', [
            'desa' => $idDesa ? Desa::with('kecamatan')->find($idDesa) : null,
            'rekap' => $idDesa ? $sampah->rekapLldikti($filter) : collect(),
            'total' => $idDesa ? $sampah->total($filter) : null,
            'isKetua' => $this->isKetua($request->user()),
        ]);
    }

    public function listdata(Request $request)
    {
        return view('kpicapaian.listdata', ['isKetua' => $this->isKetua($request->user())]);
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $isKetua = $this->isKetua($request->user());
        $data = Kpicapaian::visibleToMahasiswa($request->user()->email)
            ->with(['kpi', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
            ->orderByDesc('bulan')->orderByDesc('created_at')
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('lokasi', function (Kpicapaian $row) {
                $lokasi = $row->pjdesa?->mahasiswa?->user?->locationProgram?->nama_lokasi;
                $desa = $row->pjdesa?->desa;
                if (! $lokasi || ! $desa?->kecamatan) {
                    return 'Tidak Diketahui';
                }

                return e($lokasi).'<br /> '.e($desa->kecamatan->kecamatan).', '.e($desa->desa);
            })
            ->addColumn('nama_kpi', fn (Kpicapaian $row) => $row->kpi->nama_kpi ?? '')
            // Label tampilan; kolom 'bulan' mentah (Y-m-d) tetap dikirim untuk order
            ->addColumn('bulan_label', fn (Kpicapaian $row) => $row->bulan ? Carbon::parse($row->bulan)->translatedFormat('F Y') : '-')
            ->editColumn('permasalahan', fn (Kpicapaian $row) => nl2br(e($row->permasalahan)))
            ->editColumn('solusi', fn (Kpicapaian $row) => nl2br(e($row->solusi)))
            ->editColumn('kendala', fn (Kpicapaian $row) => nl2br(e($row->kendala)))
            ->editColumn('status_capaian', fn (Kpicapaian $row) => Kpicapaian::statusBadge($row->status_capaian))
            ->editColumn('tautan', fn (Kpicapaian $row) => HtmlSanitizer::link($row->tautan))
            // Tombol aksi hanya untuk ketua
            ->addColumn('action', fn (Kpicapaian $row) => $isKetua ? ActionButtons::make(
                urlEdit: url('kpicapaian/edit/'.$row->id_capaian),
                urlDelete: url('kpicapaian/destroy'),
                idField: 'id_capaian',
                idValue: $row->id_capaian,
            ) : '')
            ->rawColumns(['lokasi', 'action', 'tautan', 'status_capaian', 'permasalahan', 'solusi', 'kendala'])
            ->make(true);
    }

    public function tambah(Request $request)
    {
        abort_unless($this->isKetua($request->user()), 403);

        return view('kpicapaian.tambah', ['kpi' => Kpi::orderBy('nama_kpi')->get()]);
    }

    public function insert(KpicapaianRequest $request)
    {
        try {
            Kpicapaian::create($this->payload($request));
        } catch (UniqueConstraintViolationException) {
            // Race: dua submit bersamaan lolos validasi, UNIQUE(email,bulan) menolak yang kedua
            return $this->failed(KpicapaianRequest::DUPLIKAT_BULAN);
        }

        return $this->saved('Capaian Key performance indicator berhasil disimpan');
    }

    public function edit(Request $request, string $id_capaian)
    {
        abort_unless($this->isKetua($request->user()), 403);

        return view('kpicapaian.edit', [
            'data' => Kpicapaian::ownedBy($request->user())->findOrFail($id_capaian),
            'kpi' => Kpi::orderBy('nama_kpi')->get(),
        ]);
    }

    public function update(KpicapaianRequest $request)
    {
        $capaian = Kpicapaian::ownedBy($request->user())->find($request->validated('id_capaian'));
        if (! $capaian) {
            return $this->notFound();
        }

        try {
            $capaian->update($this->payload($request));
        } catch (UniqueConstraintViolationException) {
            return $this->failed(KpicapaianRequest::DUPLIKAT_BULAN);
        }

        return $this->saved('Capaian key performance indicator berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        abort_unless($this->isKetua($request->user()), 403);

        $capaian = Kpicapaian::ownedBy($request->user())->find($request->input('id_capaian'));
        if (! $capaian) {
            return $this->notFound();
        }
        $capaian->delete();

        return $this->deleted();
    }

    public function export(Request $request, KpiSampahService $sampah)
    {
        $user = $request->user();
        $file = 'capaian_kpi_'.date('Y-m-d_H-i-s').'.xlsx';

        if ($user->role !== 'mahasiswa') {
            return Excel::download(new CapaiankpiExport(), $file);
        }

        // Mahasiswa: blok per periode (data sampah desa + capaian KPI); tanpa desa → sampah kosong
        $idDesa = $sampah->desaMahasiswa($user->email);
        $capaian = Kpicapaian::visibleToMahasiswa($user->email)
            ->with(['kpi', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
            ->orderByDesc('bulan')->orderByDesc('created_at')->get();

        return Excel::download(new CapaianKpiPeriodeSheet(
            $idDesa ? $sampah->rekapLldikti(['id_desa' => $idDesa]) : collect(),
            $capaian,
        ), $file);
    }

    // Ketua kelompok = mahasiswa yang punya baris Pjdesa
    private function isKetua(User $user): bool
    {
        return Pjdesa::where('email', $user->email)->exists();
    }

    // Email & id_pjdesa dari user login, bukan input (anti mass assignment)
    private function payload(KpicapaianRequest $request): array
    {
        $email = $request->user()->email;

        return $request->safe()->only(self::FIELDS) + [
            'bulan' => $request->bulan(),
            'email' => $email,
            'id_pjdesa' => Pjdesa::where('email', $email)->value('id_pjdesa'),
        ];
    }
}
