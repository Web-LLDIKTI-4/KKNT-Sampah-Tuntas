<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Mahasiswa;

class PttugasakhirController extends Controller
{    
    public function index()
    {  
        return view('user.tugasakhir.index');
    }
    public function listdata()
    {
        dd(Auth::user()->email);
        return view('user.tugasakhir.listdata');
    }
    public function listdataserver(Request $request)
    {
        if ($request->ajax()) {
            $data = Mahasiswa::where("kodept",Auth::user()->email)->get();
        
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('tugas_akhir', function($row) {
                    return $row->tugasakhir->tautan ?? '-';
                })
                
                ->rawColumns(['tugas_akhir'])
                ->make(true);
        }
    }

}