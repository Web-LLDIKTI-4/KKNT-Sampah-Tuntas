<?php

namespace App\Http\Controllers;

use App\Exports\PendataanPemilahanSampahByMhsExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\PendataanPemilahanSampahRequest;
use App\Models\Mahasiswa;
use App\Models\PendataanPemilahanSampah;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Facades\DataTables;

class PendataanPemilahanSampahController extends StudentLogReportController
{
    use RespondsWithJson;

    protected function viewPrefix(): string
    {
        return 'pendataanpemilahan.dpl';
    }

    protected function routePrefix(): string
    {
        return 'pendataanpemilahan';
    }

    protected function logRelation(): string
    {
        return 'pendataanPemilahanSampah';
    }

    protected function constrainCountedLogs($query): void {}

    protected function showGroupActionColumn(): bool
    {
        return false;
    }

    protected function groupedStudentName(Mahasiswa $student): string
    {
        return '<a href="'.e(url(
            $this->routePrefix().'/listdata/'.rawurlencode($student->email)
        )).'">'.e($student->nama ?? 'Nama tidak tersedia').'</a>';
    }

    public function index(?Request $request = null)
    {
        $request ??= request();

        return $request->user()->role === 'mahasiswa'
            ? view('pendataanpemilahan.index')
            : view('pendataanpemilahan.dpl.index');
    }

    public function listdata(?Request $request = null)
    {
        $request ??= request();

        return $request->user()->role === 'mahasiswa'
            ? view('pendataanpemilahan.listdata')
            : view('pendataanpemilahan.dpl.listdatagroup');
    }

    public function listdataserver(Request $request, ?string $email = null)
    {
        if ($email !== null) {
            return parent::listdataserver($request, $email);
        }

        abort_unless($request->ajax() && $request->user()->role === 'mahasiswa', 404);

        $data = PendataanPemilahanSampah::ownedBy($request->user())
            ->without('mahasiswa')
            ->orderByDesc('tanggal')
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', fn (PendataanPemilahanSampah $row) => ActionButtons::make(
                urlEdit: url('pendataanpemilahan/edit/'.$row->id_pendataan),
                urlDelete: url('pendataanpemilahan/destroy'),
                idField: 'id_pendataan',
                idValue: $row->id_pendataan,
            ))
            ->editColumn('tanggal', fn (PendataanPemilahanSampah $row) => date('d-m-Y', strtotime($row->tanggal)))
            ->editColumn('memilah', fn (PendataanPemilahanSampah $row) => $row->memilah ? 'Ya' : 'Tidak')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function detail(Request $request, string $email)
    {
        $this->authorizeStudent($request, $email);

        return view('pendataanpemilahan.dpl.listdata', compact('email'));
    }

    protected function detailTable(string $email): DataTableAbstract
    {
        return DataTables::of(PendataanPemilahanSampah::where('email', $email)
            ->without('mahasiswa')
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get())
            ->addIndexColumn()
            ->editColumn('tanggal', fn (PendataanPemilahanSampah $row) => date('d-m-Y', strtotime($row->tanggal)))
            ->editColumn('memilah', fn (PendataanPemilahanSampah $row) => $row->memilah ? 'Ya' : 'Tidak');
    }

    protected function exportFor(string $email)
    {
        return $this->download($email, null);
    }

    public function export(Request $request, ?string $email = null): BinaryFileResponse
    {
        if ($email === null) {
            abort_unless($request->user()->role === 'mahasiswa', 404);
            $email = $request->user()->email;
        } else {
            abort_unless($request->user()->role !== 'mahasiswa' && Mahasiswa::canBeViewedBy($request->user(), $email), 404);
        }

        $validated = $request->validate([
            'bulan' => ['nullable', 'regex:/^(19|20)\d{2}-(0[1-9]|1[0-2])$/'],
        ]);
        $bulan = $validated['bulan'] ?? null;

        return $this->download($email, $bulan);
    }

    private function download(string $email, ?string $bulan): BinaryFileResponse
    {
        return Excel::download(
            new PendataanPemilahanSampahByMhsExport($email, $bulan),
            'pendataan_pemilahan_'.$email.'_'.($bulan ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx'
        );
    }

    public function tambah()
    {
        return view('pendataanpemilahan.tambah');
    }

    public function insert(PendataanPemilahanSampahRequest $request)
    {
        PendataanPemilahanSampah::create(
            $request->safe()->only(PendataanPemilahanSampahRequest::FIELDS) + ['email' => $request->user()->email]
        );

        return $this->saved('Data pemilahan sampah berhasil disimpan');
    }

    public function edit(Request $request, string $id_pendataan)
    {
        return view('pendataanpemilahan.edit', [
            'data' => PendataanPemilahanSampah::ownedBy($request->user())->findOrFail($id_pendataan),
        ]);
    }

    public function update(PendataanPemilahanSampahRequest $request)
    {
        $data = PendataanPemilahanSampah::ownedBy($request->user())->find($request->validated('id_pendataan'));
        if (! $data) {
            return $this->notFound();
        }

        $data->update($request->safe()->only(PendataanPemilahanSampahRequest::FIELDS));

        return $this->saved('Data pemilahan sampah berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $data = PendataanPemilahanSampah::ownedBy($request->user())->find($request->input('id_pendataan'));
        if (! $data) {
            return $this->notFound();
        }

        $data->delete();

        return $this->deleted();
    }
}
