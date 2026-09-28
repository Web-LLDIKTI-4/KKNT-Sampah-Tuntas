<?php

namespace App\Http\Controllers;

use App\Exports\KPITargetExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\KpitargetRequest;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KpitargetController extends Controller
{
    use RespondsWithJson;

    private const FIELDS = ['id_kpi', 'kegiatan', 'target', 'satuan'];

    public function index()
    {
        return view('kpitarget.index');
    }

    public function listdata()
    {
        return view('kpitarget.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Kpitarget::with('kpi')->get())
            ->addIndexColumn()
            ->addColumn('nama_kpi', fn (Kpitarget $row) => $row->kpi->nama_kpi ?? '')
            ->addColumn('action', fn (Kpitarget $row) => ActionButtons::crud(
                url('kpitarget/edit/'.$row->id_target),
                url('kpitarget/destroy'),
                'id_target',
                $row->id_target
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('kpitarget.tambah', ['kpi' => Kpi::orderBy('nama_kpi')->get()]);
    }

    public function insert(KpitargetRequest $request)
    {
        Kpitarget::create($request->safe()->only(self::FIELDS));

        return $this->saved('Key performance indicator berhasil disimpan');
    }

    public function edit(string $id_target)
    {
        return view('kpitarget.edit', [
            'data' => Kpitarget::findOrFail($id_target),
            'kpi' => Kpi::orderBy('nama_kpi')->get(),
        ]);
    }

    public function update(KpitargetRequest $request)
    {
        Kpitarget::findOrFail($request->validated('id_target'))->update($request->safe()->only(self::FIELDS));

        return $this->saved('Key performance indicator berhasil disimpan');
    }

    public function destroy(Request $request)
    {
        $target = Kpitarget::find($request->input('id_target'));
        if (! $target) {
            return $this->notFound();
        }

        if (Kpicapaian::where('id_target', $target->id_target)->exists()) {
            return $this->deleteRejected('Data tidak dapat dihapus karena sudah ada capaian');
        }

        $target->delete();

        return $this->deleted();
    }

    public function export()
    {
        return Excel::download(new KPITargetExport, 'kpi_target_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
