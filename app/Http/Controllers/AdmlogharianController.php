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
use Illuminate\Support\Collection;
use App\Exports\LogkegiatanExport;
use DB;
class AdmlogharianController extends Controller
{    
    public function index()
    {  
        return view('admin.logharian.index');
    }
    public function listdata()
    {
        return view('admin.logharian.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) { 

            $data = Mahasiswa::get();
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nm_lemb', function($row) {
                if ($row->sp) {
                    return $row->sp->nm_lemb;
                } else {
                    return 'No PT'; // or any default value you prefer
                }
            })
            ->addColumn('jumlah_log', function($row) {
                $log_mhs = Logkegiatan::where('email', $row->email)
                ->select(DB::raw('count(distinct tanggal) as count'))
                ->value('count');
                return $log_mhs;
            })
            ->addColumn('action', function($row){
                $actionBtn = '<div class="d-felx"><a href="'.url('admlogharian/permhs/'.$row->email.'').'" class="p-0 m-0">lihat data</a> </div>';
                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
        }
    }
    public function permhs(Request $request)
    {
        $email = $request->email;
        return view('admin.logharian.permhs',compact('email'));
    }
    public function permhsserver(Request $request)
    {
        if ($request->ajax()) { 
            // Menemukan semua mahasiswa dengan kodept yang sesuai
            $data = Logkegiatan::where("email", $request->email)->get();           
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function($row){
                $actionBtn = '<div class="d-felx"><a href="'.url('admlogharian/#').'" class="btn btn-sm p-0 m-0">lihat data</a> </div>';
                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
        }
    }
    public function export(){
        return Excel::download(new LogkegiatanExport, 'logharian_mahasiswa.xlsx');
    }
}