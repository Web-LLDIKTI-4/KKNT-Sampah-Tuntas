<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class PtmahasiswaController extends Controller
{    
    public function index()
    {  
        return view('mahasiswa.pt.index');
    }
    public function listdata()
    {
        return view('mahasiswa.pt.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
           
            $data = Mahasiswa::visibleTo(Auth::user())->with('sp')->get();

            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nm_lemb', function($row) {
                // Check if the 'sp' relation exists and is not null
                if ($row->sp) {
                    // Access the 'nm_lemb' property if 'sp' relation exists
                    return $row->sp->nm_lemb;
                } else {
                    // Handle the case where 'sp' relation is null
                    return 'Perguruan Tinggi tidak ditemukan'; // or any default value you prefer
                }
            })
            ->make(true);
        }
    }
}