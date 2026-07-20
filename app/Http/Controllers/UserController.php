<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Kehadiran;
use App\Models\Dpl;
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
                $mahasiswa = Mahasiswa::where('email', $createuser)->first();
                if($mahasiswa){
                    $data[] = [
                        'name' => $mahasiswa->nama,
                        'email' => $mahasiswa->email,
                        'location_program' => $mahasiswa->location_program ?? null,
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
        $data = Dpl::whereDoesntHave('user')->get();
        return view('user.listdpl', compact('data'));
    }
    
    public function insertuser(Request $request){
        if($request->createuser){
            $data = [];
            
            foreach($request->createuser as $createuser){
                
                $dpl = Dpl::where('email',$createuser)->first();
                if($dpl){
                    $data[] = [
                        'name' => $dpl->nama,
                        'email' => $createuser,
                        'location_program' => $dpl->location_program ?? null,
                        'password' => Hash::make($dpl->nidn),
                        'role' => 'dpl',
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
            'role' => $request->role,
        ];
        
        if (in_array($request->role, ['mahasiswa', 'dpl'])) {
            $data['location_program'] = $request->location_program;
            if ($request->role === 'mahasiswa' && $request->location_program) {
                Mahasiswa::where('email', $request->email)->update(['location_program' => $request->location_program]);
            }
        }

        $userHasLokasi = Mahasiswa_lokasi::where('user_in_up', $request->email)->exists();
        if (in_array($request->akses, ['pjdesa']) && in_array($request->role, ['mahasiswa']) && !$userHasLokasi) {
            // Kalo mahasiswa belum set desa tidak bisa add pj desa
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat set sebagai ketua kelompok, mahasiswa harus set lokasi kegiatan KKN terlebih dahulu!'
            ], 200);
        }

        if ($request->akses !== null && $request->role == "mahasiswa") {
            if($request->akses == 'hapuspjdesa'){
                Pjdesa::where('email', $request->email)->delete();
                $data['akses'] = null; 
            }else{
                Pjdesa::updateOrCreate(
                    ['email' => $request->email],
                    ['id_desa' => $userHasLokasi ? Mahasiswa_lokasi::where('user_in_up', $request->email)->value('id_desa') : null]
                );
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
        $locationPrograms = \App\Models\LokasiProgram::all();
        return view('user.tambah_pt',compact('role','sp','locationPrograms'));
    }
    public function insertuserpt(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'kodept' => 'required',
            'location_program' => 'required',
            'password' => 'required', // Validasi numerik
        ], [
            'name.required' => 'Nama harus di isi.',
            'kodept.required' => 'Perguruan Tinggi harus di isi.',
            'location_program.required' => 'Lokasi program harus di isi.',
            'password.required' => 'Password harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = User::where("email", $request->kodept)
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
            'location_program' => $request->location_program,
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
        $locationPrograms = \App\Models\LokasiProgram::all();
        $data=[
            'role'=>$role,
            'user'=>$user,
            'sp'=>$sp,
            'locationPrograms'=>$locationPrograms,
        ];
        return view('user.edit_pt',$data);
    }
    public function updateuserpt(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'kodept' => 'required',
            'location_program' => 'required',
        ], [
            'name.required' => 'Nama harus di isi.',
            'kodept.required' => 'Perguruan Tinggi harus di isi.',
            'location_program.required' => 'Lokasi program harus di isi.',
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
            'location_program' => $request->location_program,
        ];
        if($request->password){
            $data["password"] =Hash::make($request->password);
        }
        User::where("id",$request->id)->update($data);
        return response()->json(['success' => true,'message'=>"user berhasil dibuat"]);       
    }
}
