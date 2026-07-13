<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Logkegiatan;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use App\Exports\LogharianmhsExport;

class AdmlogkegiatanController extends Controller
{    
    public function index()
    {  
        return view('logkegiatan.dpl.index');
    }
    public function listdata()
    {
        return view('logkegiatan.dpl.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check() && Auth::user()->role == 'dpl') {
                $data = Logkegiatan::with(['mahasiswa', 'dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })
                    ->get();
            } else {
                $data = Logkegiatan::with(['mahasiswa', 'kpi'])->get();
            }           

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->mahasiswa->nama;
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb;
                })
                ->addColumn('nama_kpi', function($row){
                    return $row->kpi->nama_kpi;
                })
                ->addColumn('deskripsi', function($row){
                    
                    if (Auth::check() && Auth::user()->role == 'dpl') {
                        return $row->deskripsi.'<br><a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                    }else{
                        return 'tidak ditampilkan <br><a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                    }
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
        return Excel::download(new LogharianmhsExport, 'logharian_mahasiswa.xlsx');
    }
}