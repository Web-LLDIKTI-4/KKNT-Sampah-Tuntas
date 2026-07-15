<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Dpllaporan;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\LogbulanandplExport;


class AdmlaporandplController extends Controller
{    
    public function index()
    {  
        return view('laporandpl.index');
    }
    public function listdata()
    {
        return view('laporandpl.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            
            $data = Dpllaporan::get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('deskripsi', function($row){
                    return $row->deskripsi.'<br><a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                })
                ->addColumn('nama_bulan', function($row){
                    return Carbon::create()->month($row->bulan)->translatedFormat('F');
                })
                ->addColumn('nama_dpl', function($row){
                    return $row->user->name ?? '-';
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-pencil-square"></i></a> <a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-trash"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function export(){
        return Excel::download(new LogbulanandplExport, 'logbulanan_dpl.xlsx');
    }
}