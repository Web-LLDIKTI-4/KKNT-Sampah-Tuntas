<?php

namespace App\Http\Controllers;

use App\Exports\CapaiankpiExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\KpicapaianRequest;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\LokasiProgram;
use App\Models\Pjdesa;
use App\Models\User;
use App\Support\ActionButtons;
use App\Support\DataTableOrder;
use App\Support\HtmlSanitizer;
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

        $query = Kpicapaian::ownedBy($request->user())
            ->select('kpi_capaian.*', 'kpi.nama_kpi')
            ->leftJoin('kpi', 'kpi.id_kpi', '=', 'kpi_capaian.id_kpi')
            ->with(['pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
            ->when(! DataTableOrder::requested(), fn ($q) => $q->orderByDesc('kpi_capaian.created_at'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('lokasi', function (Kpicapaian $row) {
                $lokasi = $row->pjdesa?->mahasiswa?->user?->locationProgram?->nama_lokasi;
                $desa = $row->pjdesa?->desa;
                if (! $lokasi || ! $desa?->kecamatan) {
                    return 'Tidak Diketahui';
                }

                return e($lokasi).'<br /> '.e($desa->kecamatan->kecamatan).', '.e($desa->desa);
            })
            ->editColumn('nama_kpi', fn (Kpicapaian $row) => $row->nama_kpi ?? '')
            ->filterColumn('nama_kpi', fn ($q, $keyword) => $q->where('kpi.nama_kpi', 'like', "%{$keyword}%"))
            ->orderColumn('nama_kpi', 'kpi.nama_kpi $1')
            // Lokasi = lokasi program user + kecamatan/desa pjdesa; dicari lewat email pemilik
            ->filterColumn('lokasi', fn ($q, $keyword) => $q->where(fn ($w) => $w
                ->whereIn('kpi_capaian.email', User::select('email')->whereIn('location_program',
                    LokasiProgram::where('nama_lokasi', 'like', "%{$keyword}%")->pluck('id')->all()))
                ->orWhereIn('kpi_capaian.email', Pjdesa::select('pj_desa.email')
                    ->join('desa', 'desa.id_desa', '=', 'pj_desa.id_desa')
                    ->join('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.id_kecamatan')
                    ->where(fn ($d) => $d->where('desa.desa', 'like', "%{$keyword}%")->orWhere('kecamatan.kecamatan', 'like', "%{$keyword}%")))))
            ->orderColumn('lokasi', '(SELECT lp.nama_lokasi FROM users u JOIN lokasi_program lp ON lp.id = u.location_program WHERE u.email = kpi_capaian.email LIMIT 1) $1')
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
        Kpicapaian::create($this->payload($request));

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

        $capaian->update($this->payload($request));

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
            'email' => $email,
            'id_pjdesa' => Pjdesa::where('email', $email)->value('id_pjdesa'),
        ];
    }
}
