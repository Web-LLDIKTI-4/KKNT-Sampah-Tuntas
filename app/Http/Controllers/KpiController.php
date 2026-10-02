<?php

namespace App\Http\Controllers;

use App\Exports\KPIExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\KpiRequest;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KpiController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('kpi.index');
    }

    public function listdata()
    {
        return view('kpi.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Kpi::query())
            ->addIndexColumn()
            ->addColumn('action', fn (Kpi $row) => ActionButtons::make(
                urlEdit: url('kpi/edit/'.$row->id_kpi),
                urlDelete: url('kpi/destroy'),
                idField: 'id_kpi',
                idValue: $row->id_kpi,
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('kpi.tambah');
    }

    public function insert(KpiRequest $request)
    {
        Kpi::create($request->safe()->only('nama_kpi'));

        return $this->saved('Key performance indicator berhasil disimpan');
    }

    public function edit(string $id_kpi)
    {
        return view('kpi.edit', ['data' => Kpi::findOrFail($id_kpi)]);
    }

    public function update(KpiRequest $request)
    {
        Kpi::findOrFail($request->validated('id_kpi'))->update($request->safe()->only('nama_kpi'));

        return $this->saved('Key performance indicator berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $kpi = Kpi::find($request->input('id_kpi'));
        if (! $kpi) {
            return $this->notFound();
        }

        if (Kpicapaian::where('id_kpi', $kpi->id_kpi)->exists()) {
            return $this->deleteRejected('Hapus dulu data terkait');
        }

        $kpi->delete();

        return $this->deleted();
    }

    public function export()
    {
        return Excel::download(new KPIExport, 'kpi_'.date('d-m-Y_H-i-s').'.xlsx');
    }
}
