<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Kehadiran;
use App\Models\Dplmentoring;
use App\Models\Dpllaporan;
use App\Models\Logkegiatan;
use App\Models\Logbulanan;
use App\Models\Pjdesa;
use App\Models\Nilaikonversi;
use App\Models\Kpicapaian;
use App\Models\Satuanpendidikan;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{    
    public function index()
    {  
        return view('user.index');
    } 
    public function listdata(){
        $data = User::whereIn('role', ['mahasiswa', 'dpl','pt'])->get();
        return view('user.list',compact('data'));
    } 
    public function getdatamember(){
        $data = Mahasiswa::whereDoesntHave('user')->get();
        return view('user.listmember',compact('data'));
    }
    public function insert(Request $request){
        if($request->createuser){
            $data = [];
            
            foreach($request->createuser as $createuser){
                
                $mahasiswa = Mahasiswa::where('email',$createuser)->first();
                if($mahasiswa){
                    $data[] = [
                        'name' => $mahasiswa->nama,
                        'email' => $createuser,
                        'password' => Hash::make($mahasiswa->nim),
                        'role' => 'mahasiswa',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            //insert data baru
            User::insert($data);
            return response()->json(['success'=>"user berhasil dibuat"]);
        }else{
            return response()->json(['error'=>"user harus dipilih"]);
        }
    }
    public function adduser(){
        $role=array('dpl');
        return view('user.tambah',compact('role'));
    }
    public function insertuser(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required', // Validasi numerik
        ], [
            'name.required' => 'Nama harus di isi.',
            'email.required' => 'Email harus di isi.',
            'email.email' => 'Email harus tidak valid.',
            'password.required' => 'Password harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = User::where("email",$request->email)
                        ->exists(); // Menggunakan exists() untuk mengecek keberadaan data
            
            if ($cekdata) {
                $validator->errors()->add('email', 'Email sudah digunakan!');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200); 
        }
        $data=[
            'name'=>$request->name,
            'email'=>$request->email,
            'location_program' => $request->locationProgram->id,
            'role' =>$request->role,
            'password'=> Hash::make($request->password)
        ];
        User::insert($data);
        return response()->json(['success' => true,'message'=>"user berhasil dibuat"]);       
    }

    public function edit(Request $request){
        $data = User::find($request->id);
        $locationPrograms = \App\Models\LokasiProgram::all();
        $role=array('mahasiswa','dpl');
        $akses=array('pjdesa'=>'Set Ketua Kelompok','hapuspjdesa'=>'Hapus Akses Ketua Kelompok');
        return view('user.edit',compact('data','role','akses','locationPrograms'));
    }
    public function updateuser(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'location_program' => 'nullable|exists:lokasi_program,id'
        ], [
            'name.required' => 'Nama harus di isi.',
            'email.required' => 'Email harus di isi.',
            'email.email' => 'Email harus tidak valid.',
            'location_program.exists' => 'Lokasi program tidak valid.'
        ]);
        
        $validator->after(function ($validator) use ($request) {
            $cekdata = User::where("id", "!=", $request->id)->where("email", $request->email)->exists(); // Menggunakan exists() untuk mengecek keberadaan data
            if ($cekdata) {
                $validator->errors()->add('email', 'Email sudah digunakan!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200);
        }
        
        $data = [
            'name' => $request->name,            
            'location_program' => $request->location_program,
            'role' => $request->role,
        ];
        if ($request->akses != "null" && $request->role == "mahasiswa") {
            if($request->akses == 'hapuspjdesa'){
                $data['akses'] = null; 
            }else{
                $data['akses'] = $request->akses; 
            }
        }
        if ($request->password) {
            $data['password'] = Hash::make($request->password); 
        }
        //cek data dulu
        $cekdata = User::where("id",$request->id)->first();
        if($cekdata){
            if($cekdata->email != $request->email){//jika email berubah
                $data['email']=$request->email;
                $data_up = [
                    'email'=>$request->email
                ];
                if($request->role == "mahasiswa"){
                    //update table mahasiswa
                    Mahasiswa::where("email",$cekdata->email)->update($data_up);
                    //update table kehadiran
                    Kehadiran::where("email",$cekdata->email)->update($data_up);
                    //update dpl_mentoring
                    Dplmentoring::where("email_mahasiswa",$cekdata->email)->update(['email_mahasiswa'=>$request->email]);
                    //update log harian
                    Logkegiatan::where("email",$cekdata->email)->update($data_up);
                    //update log bulanan
                    Logbulanan::where("email",$cekdata->email)->update($data_up);
                    //update pj desa
                    Pjdesa::where("email",$cekdata->email)->update($data_up);
                    //capaian kpi
                    Kpicapaian::where("email",$cekdata->email)->update($data_up);
                    
                }else{
                    Dpllaporan::where("email",$cekdata->email)->update($data_up);
                    //update dpl_mentoring
                    Dplmentoring::where("email_dpl",$cekdata->email)->update(['email_dpl'=>$request->email]);
                    // update konversi nilai
                    Nilaikonversi::where("email_dpl",$cekdata->email)->update(['email_dpl'=>$request->email]);
                }
            }
        } 

        User::where('id', $request->id)->update($data);
        return response()->json(['success' => true, 'message' => "User berhasil diupdate"]);
    }

    public function adduserpt(){
        $role=array('pt');
        $sp = Satuanpendidikan::get();
        return view('user.tambah_pt',compact('role','sp'));
    }
    public function insertuserpt(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'kodept' => 'required',
            'password' => 'required', // Validasi numerik
        ], [
            'name.required' => 'Nama harus di isi.',
            'kodept.required' => 'Email harus di isi.',
            'password.required' => 'Password harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = User::where("email",$request->kodept)
                        ->exists(); // Menggunakan exists() untuk mengecek keberadaan data
            
            if ($cekdata) {
                $validator->errors()->add('kodept', 'kodept sudah digunakan!');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200); 
        }
        $data=[
            'name'=>$request->name,
            'email'=>$request->kodept,
            'role' =>$request->role,
            'password'=> Hash::make($request->password)
        ];
        User::insert($data);
        return response()->json(['success' => true,'message'=>"user berhasil dibuat"]);       
    }
    public function edituserpt($id){
        $role=array('pt');
        $user = User::find($id);
        $sp = Satuanpendidikan::orderByRaw("TRIM(nm_lemb) DESC")->get();
        $data=[
            'role'=>$role,
            'user'=>$user,
            'sp'=>$sp,
        ];
        return view('user.edit_pt',$data);
    }
    public function updateuserpt(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => 'Nama harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            if (!$request->kodept) {
                $validator->errors()->add('kodept', 'PT harus dipilih!');
            } else {
                $cekdata = User::where("id", "!=", $request->id)->where("email", $request->kodept)->exists();
                if ($cekdata) {
                    $validator->errors()->add('kodept', 'PT tersebut sudah digunakan oleh akun lain!');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200); 
        }
        $data=[
            'name'=>$request->name,
            'email'=>$request->kodept,
        ];
        if($request->password){
            $data["password"] =Hash::make($request->password);
        }
        User::where("id",$request->id)->update($data);
        return response()->json(['success' => true,'message'=>"user berhasil dibuat"]);       
    }
}
