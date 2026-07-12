<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Logkegiatan;
use App\Models\Logbulanan;
use App\Models\Tugasakhir;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TugasakhirController extends Controller
{    
    public function index()
    {  
        return view('member.tugasakhir.index');
    }
    public function listdata(){
        $data = Tugasakhir::where('email',Auth::user()->email)->get();
        return view('member.tugasakhir.listdata',compact('data'));
    }
    public function tambah(Request $request)
    {
        return view('member.tugasakhir.tambah');
    }
    public function insert(Request $request){
        $validator = Validator::make($request->all(), [
            'tautan' => 'required',
        ], [
            'tautan.required' => 'tautan harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {  
            $exists =  Tugasakhir::where('email',Auth::user()->email)->exists();       
            if ($exists) {
                $validator->errors()->add('tautan', 'Tugas akhir sudah ada!');
            }
        });
       
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }

        $data = [
            'email' => Auth::user()->email,
            'tautan' => $request->tautan,
            'tahun' => date('Y'),
        ];
        
        Tugasakhir::create($data);
        
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function edit(Request $request)
    {
        $data = Tugasakhir::where("email", Auth::user()->email)->where("id_tugasakhir", $request->id_tugasakhir)->first();
        return view('member.tugasakhir.edit',compact('data'));
    }
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'tautan' => 'required',
        ], [
            'tautan.required' => 'tautan harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {  
            $exists =  Tugasakhir::where('email',Auth::user()->email)->where("tautan",$request->tautan)->where("id_tugasakhir","!=",$request->id_tugasakhir)->exists();       
            if ($exists) {
                $validator->errors()->add('tautan', 'tautan sudah ada!');
            }
        });
       
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal diupdate!','errors' => $validator->errors()], 200);
        }

        $data = [
            'email' => Auth::user()->email,
            'tautan' => $request->tautan,
            'tahun' => date('Y'),
        ];
        
        Tugasakhir::where('email',Auth::user()->email)->where("id_tugasakhir", $request->id_tugasakhir)->update($data);
        
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil diupdate.'], 200);
    }
    public function destroy(Request $request){
        if (Tugasakhir::where("email", Auth::user()->email)->where("id_tugasakhir", $request->id_tugasakhir)->delete()) {
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Data gagal dihapus'], 200);
        }
    }
}