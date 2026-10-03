<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dplmentoring;
use App\Models\Nilaikonversi;
use App\Support\NilaiMahasiswaDataTable;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

use App\Exports\StructureformExport;


class AdmstructureformController extends Controller
{    
    public function index()
    {  
        return view('structureform.index');
    }
    public function listdata()
    {
        return view('structureform.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            return NilaiMahasiswaDataTable::make(Nilaikonversi::query())
                ->make(true);
        }
    }
    public function export(){
        return Excel::download(new StructureformExport, 'structure_form.xlsx');
    }
    
}