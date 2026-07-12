<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Saran;
use DataTables;
use Illuminate\Support\Facades\Validator;

class SaranController extends Controller
{    
    public function index()
    {  
        //return view('perguruantinggi.index');
    }
    public function insert(Request $request){
        $validator = Validator::make($request->all(), [
            'nama'     => 'required',
            'email'     => 'required',
            'saran'     => 'required',
        ], [
            'nama.required' => 'Nama harus diisi.',
            'email.required' => 'Email harus isi.',
            'saran.required' => 'Saran harus isi.',
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = Saran::where("email",$request->email)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('email', 'Pesan anda sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal dikirim!','errors' => $validator->errors()], 200);
        }
        $data=[
            'nama'=>$request->nama,
            'email'=>$request->email,
            'saran'=>$request->saran,
        ];
        Saran::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil dikirim'], 200);
    }
}