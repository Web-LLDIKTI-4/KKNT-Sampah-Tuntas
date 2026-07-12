<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Tugasakhir;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\LaptugasakhirExport;

class LaptugasakhirController extends Controller
{    
    public function index()
    {  
        return view('admin.laptugasakhir.index');
    }
    public function listdata()
    {
        return view('admin.laptugasakhir.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Tugasakhir::get();
        
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row) {
                    return $row->mahasiswa->nim ?? '-';
                })
                ->addColumn('nama', function($row) {
                    return $row->mahasiswa->nama ?? '-';
                })
                ->addColumn('nm_lemb', function($row) {
                    return $row->mahasiswa->sp->nm_lemb ?? '-';
                })
                ->addColumn('tautan', function($row) {
                    return $row->tautan ? '<a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>' : null;
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '<div class="d-flex">
                        <a href="javascript:void(0)" id="hapus_'.$row->id_tugasakhir.'" class="btn btn-sm p-0 m-0">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'tautan'])
                ->make(true);
        }
        
    }
    public function export(){
        return Excel::download(new LaptugasakhirExport, 'capaian_kpi.xlsx');
    }

}