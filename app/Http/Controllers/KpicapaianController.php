<?php

namespace App\Http\Controllers;

use App\Exports\CapaiankpiExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\KpicapaianRequest;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Pjdesa;
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

    public function index()
    {
        return view('kpicapaian.index');
    }

    public function listdata()
    {
        return view('kpicapaian.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Kpicapaian::ownedBy($request->user())
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
            ->addColumn('action', fn (Kpicapaian $row) => ActionButtons::make(
                urlEdit: url('kpicapaian/edit/'.$row->id_capaian),
                urlDelete: url('kpicapaian/destroy'),
                idField: 'id_capaian',
                idValue: $row->id_capaian,
            ))
            ->rawColumns(['lokasi', 'action', 'tautan', 'status_capaian', 'permasalahan', 'solusi', 'kendala'])
            ->make(true);
    }

    public function tambah()
    {
        return view('kpicapaian.tambah', [
            'kpi' => Kpi::orderBy('nama_kpi')->get(),
        ]);
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
        $capaian = Kpicapaian::ownedBy($request->user())->findOrFail($id_capaian);

        return view('kpicapaian.edit', [
            'data' => $capaian,
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
        $capaian = Kpicapaian::ownedBy($request->user())->find($request->input('id_capaian'));
        if (! $capaian) {
            return $this->notFound();
        }

        $capaian->delete();

        return $this->deleted();
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $email = $user->role === 'mahasiswa' ? $user->email : null;

        return Excel::download(new CapaiankpiExport($email), 'capaian_kpi_'.date('Y-m-d_H-i-s').'.xlsx');
    }

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
