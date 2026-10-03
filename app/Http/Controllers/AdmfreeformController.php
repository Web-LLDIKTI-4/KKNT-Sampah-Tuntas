<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Support\NilaiMahasiswaDataTable;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

use App\Exports\FreeformExport;

class AdmfreeformController extends Controller
{    
    public function index()
    {  
        return view('freeform.index');
    }
    public function listdata()
    {
        return view('freeform.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            return NilaiMahasiswaDataTable::make(Freeform::query())
                ->make(true);
        }
    }
    public function export(){
        return Excel::download(new FreeformExport, 'free_form.xlsx');
    }
    
}