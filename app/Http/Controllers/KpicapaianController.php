<?php

namespace App\Http\Controllers;

use App\Exports\CapaiankpiExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\KpicapaianRequest;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\Pjdesa;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KpicapaianController extends Controller
{
    use RespondsWithJson;

    private const FIELDS = ['id_kpi', 'id_target', 'realisasi', 'status_capaian', 'tautan', 'permasalahan', 'solusi', 'kendala'];

    private const BADGE = ['Y' => 'bg-success', 'P' => 'bg-warning', 'N' => 'bg-danger'];

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
            ->with(['kpi', 'target', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
            ->orderByDesc('created_at')
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
            ->addColumn('kegiatan', fn (Kpicapaian $row) => $row->target->kegiatan ?? '')
            ->addColumn('target_kpi', fn (Kpicapaian $row) => Kpicapaian::formatAngka($row->target?->target).' '.($row->target->satuan ?? ''))
            ->editColumn('realisasi', fn (Kpicapaian $row) => Kpicapaian::formatAngka($row->realisasi).' '.$row->satuan)
            ->addColumn('capaian', fn (Kpicapaian $row) => Kpicapaian::formatAngka($row->capaianPersen()).'%')
            ->editColumn('permasalahan', fn (Kpicapaian $row) => nl2br(e($row->permasalahan)))
            ->editColumn('solusi', fn (Kpicapaian $row) => nl2br(e($row->solusi)))
            ->editColumn('kendala', fn (Kpicapaian $row) => nl2br(e($row->kendala)))
            ->editColumn('status_capaian', fn (Kpicapaian $row) => static::statusBadge($row->status_capaian))
            ->editColumn('tautan', fn (Kpicapaian $row) => HtmlSanitizer::link($row->tautan))
            ->addColumn('action', fn (Kpicapaian $row) => ActionButtons::crud(
                url('kpicapaian/edit/'.$row->id_capaian),
                url('kpicapaian/destroy'),
                'id_capaian',
                $row->id_capaian
            ))
            ->rawColumns(['lokasi', 'action', 'tautan', 'status_capaian', 'permasalahan', 'solusi', 'kendala'])
            ->make(true);
    }

    public function kpitarget(Request $request)
    {
        $request->validate(['id_kpi' => ['nullable', 'uuid']]);

        return view('kpicapaian.kpitarget', [
            'kpitarget' => Kpitarget::where('id_kpi', $request->input('id_kpi'))->orderBy('kegiatan')->get(),
        ]);
    }

    public function tambah()
    {
        return view('kpicapaian.tambah', [
            'kpi' => Kpi::orderBy('nama_kpi')->get(),
            'kpitarget' => collect(),
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
            'kpitarget' => Kpitarget::where('id_kpi', $capaian->id_kpi)->orderBy('kegiatan')->get(),
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
        $target = Kpitarget::findOrFail($request->validated('id_target'));

        // Satuan realisasi dikunci mengikuti satuan target
        return $request->safe()->only(self::FIELDS) + [
            'email' => $email,
            'satuan' => $target->satuan,
            'id_pjdesa' => Pjdesa::where('email', $email)->value('id_pjdesa'),
        ];
    }

    private static function statusBadge(?string $status): string
    {
        $status = array_key_exists((string) $status, Kpicapaian::STATUS) ? $status : 'N';

        return '<span class="badge '.self::BADGE[$status].'">'.Kpicapaian::STATUS[$status].'</span>';
    }
}
