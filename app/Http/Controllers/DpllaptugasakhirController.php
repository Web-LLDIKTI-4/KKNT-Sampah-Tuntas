<?php

namespace App\Http\Controllers;

use App\Exports\LaptugasakhirExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Dpl\NilaiTugasakhirRequest;
use App\Models\Dplmentoring;
use App\Models\Tugasakhir;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DpllaptugasakhirController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('laptugasakhir.dpl.index');
    }

    public function listdata()
    {
        return view('laptugasakhir.dpl.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $email = $request->user()->email;
        $data = Tugasakhir::with('mahasiswa.sp')
            ->whereHas('dplmentoring', fn ($q) => $q->where('email_dpl', $email))
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => '<div class="text-center">'.e($row->mahasiswa->nim ?? '-').'</div>')
            ->addColumn('nama', fn ($row) => $row->mahasiswa->nama ?? '-')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? '-')
            ->editColumn('tautan', fn ($row) => HtmlSanitizer::link($row->tautan))
            ->addColumn('action', fn ($row) => '<div class="d-flex">'
                .'<form method="post" data-ajax-form action="'.e(url('dpllaptugasakhir/nilai')).'" id="form-nilai-'.e($row->id_tugasakhir).'">'
                .csrf_field().method_field('PUT')
                .'<input type="hidden" name="id_tugasakhir" value="'.e($row->id_tugasakhir).'">'
                .'<input type="number" name="nilai_dpl" min="0" max="100" class="form-control form-control-sm col-md-5 text-center" value="'.e($row->nilai_dpl).'">'
                .'</form></div>')
            ->rawColumns(['nim', 'action', 'tautan'])
            ->make(true);
    }

    public function nilai(NilaiTugasakhirRequest $request)
    {
        $tugas = Tugasakhir::find($request->validated('id_tugasakhir'));
        if (! $tugas || ! Dplmentoring::isMentor($request->user(), $tugas->email)) {
            return $this->notFound();
        }

        $tugas->update(['nilai_dpl' => (int) $request->validated('nilai_dpl'), 'email_dpl' => $request->user()->email]);

        return $this->saved('Data berhasil diupdate!');
    }

    public function export()
    {
        return Excel::download(new LaptugasakhirExport, 'laporan_akhir_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
