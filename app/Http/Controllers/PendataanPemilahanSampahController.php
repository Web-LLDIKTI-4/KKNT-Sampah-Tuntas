<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\PemilahanExportRequest;
use App\Http\Requests\Mahasiswa\PendataanPemilahanSampahRequest;
use App\Models\Mahasiswa;
use App\Models\PendataanPemilahanSampah;
use App\Support\ActionButtons;
use App\Support\XlsxStreamer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $this->sanitizeDataTablesInput();

        $data = PendataanPemilahanSampah::ownedBy($request->user())
            ->unless($this->userSorts(), fn ($q) => $q->orderByDesc('tanggal'));

        return $this->withPemilahanFilters(DataTables::eloquent($data))
            ->addColumn('action', fn (PendataanPemilahanSampah $row) => ActionButtons::make(
                urlEdit: url('pendataanpemilahan/edit/'.$row->id_pendataan),
                urlDelete: url('pendataanpemilahan/destroy'),
                idField: 'id_pendataan',
                idValue: $row->id_pendataan,
            ))
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
        return $this->withPemilahanFilters(DataTables::eloquent(PendataanPemilahanSampah::where('email', $email)
            ->unless($this->userSorts(), fn ($q) => $q->orderBy('tanggal')->orderBy('created_at'))));
    }

    // Eloquent: sort tanggal kronologis di SQL; search pakai teks tampilan (d-m-Y, Ya/Tidak)
    private function withPemilahanFilters(DataTableAbstract $table): DataTableAbstract
    {
        return $table->addIndexColumn()
            ->editColumn('tanggal', fn (PendataanPemilahanSampah $row) => date('d-m-Y', strtotime($row->tanggal)))
            ->filterColumn('tanggal', fn ($q, $k) => $this->filterTanggal($q, $k))
            ->editColumn('memilah', fn (PendataanPemilahanSampah $row) => $row->memilah ? 'Ya' : 'Tidak')
            ->filterColumn('memilah', function ($q, $k) {
                $k = strtolower(trim($k));
                [$ya, $tidak] = [str_contains('ya', $k), str_contains('tidak', $k)];
                match (true) {
                    $ya && $tidak => $q->whereNotNull('memilah'),
                    $ya => $q->where('memilah', true),
                    $tidak => $q->where('memilah', false),
                    default => $q->whereRaw('1 = 0'),
                };
            });
    }

    protected function exportFor(string $email)
    {
        return $this->download($email, null);
    }

    public function export(Request $request, ?string $email = null): StreamedResponse
    {
        if ($email === null) {
            abort_unless($request->user()->role === 'mahasiswa', 404);
            $email = $request->user()->email;
        } else {
            abort_unless($request->user()->role !== 'mahasiswa' && Mahasiswa::canBeViewedBy($request->user(), $email), 404);
        }

        // Resolved after the access check so unauthorized emails stay 404 (signature must match the base)
        return $this->download($email, app(PemilahanExportRequest::class)->validated('bulan'));
    }

    // Same query as the detail table (student's own list uses the same rows), optional month filter
    private function download(string $email, ?string $bulan): StreamedResponse
    {
        $this->sanitizeDataTablesInput();
        $rows = $this->dataTablesExportQuery($this->detailTable($email), 'pendataan_pemilahan_sampah.id_pendataan')
            ->when($bulan, fn ($query, $bulan) => $query->whereYear('tanggal', substr($bulan, 0, 4))
                ->whereMonth('tanggal', substr($bulan, 5, 2)))
            ->lazy(1000);

        return (new XlsxStreamer('Pendataan Pemilahan Sampah', [
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Nama Kepala Keluarga', 'nama_kepala_keluarga', 28),
            XlsxStreamer::column('Anggota Keluarga', 'jumlah_anggota_keluarga', 12),
            XlsxStreamer::column('Alamat Rumah', 'alamat_rumah', 40),
            XlsxStreamer::column('RT', 'rt', 6),
            XlsxStreamer::column('RW', 'rw', 6),
            XlsxStreamer::column('Memilah', fn (PendataanPemilahanSampah $row) => $row->memilah ? 'Ya' : 'Tidak', 10),
            XlsxStreamer::column('Organik (kg)', fn (PendataanPemilahanSampah $row) => (float) $row->organik_kg, 12),
            XlsxStreamer::column('Anorganik (kg)', fn (PendataanPemilahanSampah $row) => (float) $row->anorganik_kg, 12),
            XlsxStreamer::column('Residu (kg)', fn (PendataanPemilahanSampah $row) => (float) $row->residu_kg, 12),
        ]))
            ->info([
                'Mahasiswa' => Mahasiswa::where('email', $email)->value('nama'),
                'Email' => $email,
                'Bulan' => $bulan ?? 'Semua',
                'Kata kunci' => request('search.value'),
            ])
            ->download('pendataan_pemilahan_'.$email.'_'.($bulan ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx', $rows);
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
