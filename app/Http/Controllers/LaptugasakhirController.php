<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\KategoriKegiatan;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\BuildsTugasakhirTable;

class LaptugasakhirController extends Controller
{    
    use BuildsTugasakhirTable;

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
            $this->sanitizeDataTablesInput();
            return $this->tugasakhirTable($request->user())
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
    public function export(Request $request)
    {
        return $this->tugasakhirExport($request, 'tugas_akhir_'.date('d-m-Y_H-i-s').'.xlsx');
    }

}