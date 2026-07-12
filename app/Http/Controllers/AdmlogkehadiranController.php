<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Kehadiran;
use App\Models\Dplmentoring;

use Maatwebsite\Excel\Facades\Excel;

class AdmlogkehadiranController extends Controller
{    
    public function index()
    {  
        return view('admin.logkehadiran.index');
    }
    public function listdata()
    {
        return view('admin.logkehadiran.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check() && Auth::user()->role == 'dpl') {
                $data = Kehadiran::with(['mahasiswa', 'dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })
                    ->get();
            } else {
                $data = Kehadiran::with(['mahasiswa'])->get();
            }
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->mahasiswa->nama ?? '-';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? '-';
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-pencil-square"></i></a> <a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-trash"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
}