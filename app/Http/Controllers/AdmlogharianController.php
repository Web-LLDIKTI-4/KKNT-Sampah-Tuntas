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
use App\Support\ActionButtons;
class AdmlogharianController extends Controller
{    
    public function index()
    {  
        return view('logharian.index');
    }
    public function listdata()
    {
        return view('logharian.listdata');
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
                    return 'Perguruan Tinggi tidak ditemukan'; // or any default value you prefer
                }
            })
            ->addColumn('jumlah_log', function($row) {
                $log_mhs = Logkegiatan::where('email', $row->email)
                ->select(DB::raw('count(distinct tanggal) as count'))
                ->value('count');
                return $log_mhs;
            })
            ->addColumn('action', function($row){
                return ActionButtons::make(urlView: url('admlogharian/permhs/'.rawurlencode($row->email)));
            })
            ->rawColumns(['action'])
            ->make(true);
        }
    }
    public function permhs(Request $request)
    {
        $email = $request->email;
        return view('logharian.permhs',compact('email'));
    }
    public function permhsserver(Request $request)
    {
        if ($request->ajax()) { 
            // Menemukan semua mahasiswa dengan kodept yang sesuai
            $data = Logkegiatan::where("email", $request->email)->get();           
            return DataTables::of($data)
            ->addIndexColumn()
            ->make(true);
        }
    }
    public function export(){
        return Excel::download(new LogkegiatanExport, 'logharian_mahasiswa.xlsx');
    }
}