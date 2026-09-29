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
        return view('laptugasakhir.index');
    }
    public function listdata()
    {
        return view('laptugasakhir.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $query = Tugasakhir::query();

            if (auth()->user()->role === 'dpl') {
                $data = Tugasakhir::whereHas('dplmentoring', function ($q) {
                    $q->where('email_dpl', auth()->user()->email);
                })->get();
            }
        
            return Datatables::of($query)
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
                    return \App\Support\HtmlSanitizer::link($row->tautan) ?: null;
                })
                ->rawColumns(['tautan'])
                ->make(true);
        }
        
    }
    public function export(){
        return Excel::download(new LaptugasakhirExport, 'capaian_kpi.xlsx');
    }

}