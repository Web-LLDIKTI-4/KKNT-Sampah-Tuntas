<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\KpisampahRequest;
use App\Models\Desa;
use App\Models\Kpisampah;
use App\Models\Pjdesa;
use App\Services\KpiSampahService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KpisampahController extends Controller
{
    use RespondsWithJson;

    private const ANGKA = ['jml_rw', 'jml_penduduk', 'jml_rumah', 'jml_rumah_memilah', 'organik_metode_unit', 'anorganik_metode_unit'];

    private const BERAT = ['timbulan', 'organik_sumber', 'organik_dlh', 'anorganik_sumber', 'pengurangan', 'belum_terkelola'];

    public function index()
    {
        return view('kpisampah.index');
    }

    public function listdata()
    {
        return view('kpisampah.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $data = Kpisampah::ownedBy($request->user())
            ->with('desa.kecamatan')
            ->orderByDesc('bulan')
            ->get();

        $table = DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('bulan', fn (Kpisampah $row) => $row->bulan->translatedFormat('F Y'))
            ->addColumn('kecamatan', fn (Kpisampah $row) => $row->desa?->kecamatan?->kecamatan ?? '-')
            ->addColumn('kelurahan', fn (Kpisampah $row) => $row->desa?->desa ?? '-')
            ->editColumn('persen_ketaatan', fn (Kpisampah $row) => Kpisampah::formatPersen($row->persen_ketaatan))
            ->editColumn('persen_pengurangan', fn (Kpisampah $row) => Kpisampah::formatPersen($row->persen_pengurangan));

        foreach (self::ANGKA as $kolom) {
            $table->editColumn($kolom, fn (Kpisampah $row) => Kpisampah::formatAngka($row->$kolom));
        }
        foreach (self::BERAT as $kolom) {
            $table->editColumn($kolom, fn (Kpisampah $row) => Kpisampah::formatAngka($row->$kolom, 2));
        }

        return $table->make(true);
    }

    public function tambah(Request $request, KpiSampahService $sampah)
    {
        $idDesa = $sampah->desaKetua($request->user()->email);

        return view('kpisampah.form', [
            'data' => null,
            'desa' => $idDesa ? Desa::with('kecamatan')->find($idDesa) : null,
        ]);
    }

    public function insert(KpisampahRequest $request)
    {
        try {
            Kpisampah::create($this->payload($request) + ['id_desa' => $request->idDesa()]);
        } catch (UniqueConstraintViolationException) {
            return $this->duplikat();
        }

        return $this->saved('Data sampah bulanan berhasil disimpan');
    }

    public function edit(Request $request, string $id_sampah)
    {
        $data = Kpisampah::ownedBy($request->user())->with('desa.kecamatan')->findOrFail($id_sampah);

        return view('kpisampah.form', ['data' => $data, 'desa' => $data->desa]);
    }

    public function update(KpisampahRequest $request)
    {
        $data = Kpisampah::ownedBy($request->user())->find($request->validated('id_sampah'));
        if (! $data) {
            return $this->notFound();
        }

        try {
            $data->update($this->payload($request));
        } catch (UniqueConstraintViolationException) {
            return $this->duplikat();
        }

        return $this->saved('Data sampah bulanan berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $data = Kpisampah::ownedBy($request->user())->find($request->input('id_sampah'));
        if (! $data) {
            return $this->notFound();
        }

        $data->delete();

        return $this->deleted();
    }

    // Race lolos validasi tapi kena UNIQUE(email, bulan); format sama dengan error validasi AJAX
    private function duplikat(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Data gagal disimpan!',
            'errors' => ['bulan' => [KpisampahRequest::PESAN_DUPLIKAT]],
        ]);
    }

    private function payload(KpisampahRequest $request): array
    {
        $email = $request->user()->email;
        $data = $request->safe()->only(KpisampahRequest::FIELDS);
        $data['bulan'] .= '-01';

        return KpiSampahService::hitung($data) + [
            'email' => $email,
            'id_pjdesa' => Pjdesa::where('email', $email)->value('id_pjdesa'),
        ];
    }
}
