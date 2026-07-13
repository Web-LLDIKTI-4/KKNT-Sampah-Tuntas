<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dplmentoring;
use App\Models\Freeform;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

use App\Exports\FreeformExport;

class AdmfreeformController extends Controller
{    
    public function index()
    {  
        return view('freeform.index');
    }
    public function listdata()
    {
        return view('freeform.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Freeform::with(['mahasiswa'])->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row){
                    return $row->mahasiswa->nim ?? '-';
                })
                ->addColumn('nama', function($row){
                    return $row->mahasiswa->nama ?? '-';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? '-';
                })
                ->addColumn('prodi', function($row){
                    return $row->mahasiswa->prodi ?? '-';
                })
                ->addColumn('nilai_akhir', function($row){
                    $nilai_dpl = is_numeric($row->nilai_dpl) ? $row->nilai_dpl : 0;
                    $nilai_dpa = is_numeric($row->nilai_dpa) ? $row->nilai_dpa : 0;
                    return ($nilai_dpl + $nilai_dpa)/2;
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"> <a href="javascript:void(0)" id="hapus_'.$row->id_freeform.'"  class="btn btn-sm p-0 m-0"><i class="fa fa-trash"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action','nilai_akhir'])
                ->make(true);
        }
    }
    public function export(){
        return Excel::download(new FreeformExport, 'free_form.xlsx');
    }
    
}