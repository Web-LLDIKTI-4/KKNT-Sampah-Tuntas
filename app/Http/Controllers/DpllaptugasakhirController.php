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

class DpllaptugasakhirController extends Controller
{    
    public function index()
    {  
        return view('laptugasakhir.dpl.index');
    }
    public function listdata()
    {
        return view('laptugasakhir.dpl.listdata');
    }
    public function listdataserver(Request $request)
    {
        $data = Tugasakhir::with(['mahasiswa', 'dplmentoring'])
                ->whereHas('dplmentoring', function ($query) {
                    $query->where('email_dpl', Auth::user()->email);
                })
                ->get();

        if ($request->ajax()) {
        
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row) {
                    return '<div class="text-center">'.($row->mahasiswa->nim ?? '-').'</div>';
                })
                ->addColumn('nama', function($row) {
                    return $row->mahasiswa->nama ?? '-';
                })
                ->addColumn('nm_lemb', function($row) {
                    return $row->mahasiswa->sp->nm_lemb ?? '-';
                })
                ->addColumn('tautan', function($row) {
                    return $row->tautan ? '<a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>' : null;
                })
                ->addColumn('action', function($row) {
                    $csrf = csrf_field();
                    $methodField = method_field('PUT');
                    $actionBtn = '<div class="d-flex">
                        <form method="post" action="'.url('dpllaptugasakhir/nilai').'" id="form-nilai-'.$row->id_tugasakhir.'">'.
                            $csrf.
                            $methodField. 
                            '<input type="hidden" name="id_tugasakhir" value="'.$row->id_tugasakhir.'">'.                         
                            '<input type="number" name="nilai_dpl" class="form-control form-control-sm col-md-5 text-center" value="'.$row->nilai_dpl.'">'.
                        '</form>
                        </div>';
                    return $actionBtn;
                })
                ->rawColumns(['nim', 'action', 'tautan'])
                ->make(true);
        }
        
    }
    public function nilai(Request $request){
        $validator = Validator::make($request->all(), [
            'nilai_dpl' => 'required|numeric',
        ], [            
            'nilai_dpl.required' => 'Nilai DPL harus di isi.',
            'nilai_dpl.numeric' => 'Nilai DPL harus angka.',
        ]);
        
        $validator->after(function ($validator) use ($request) {
            
        });
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200);
        }
        
        $data = [
            'nilai_dpl' => $request->nilai_dpl,
            'email_dpl' => Auth::user()->email,
        ];
        Tugasakhir::where(['id_tugasakhir'=>$request->id_tugasakhir])->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate!'
        ], 200);  
    }
    public function export(){
        return Excel::download(new LaptugasakhirExport, 'laporan_akhir_'.date('Y-m-d_H-i-s').'.xlsx');
    }

}