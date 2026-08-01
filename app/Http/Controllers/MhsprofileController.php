<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\Satuanpendidikan;
use App\Models\LokasiProgram;
use App\Models\Kecamatan;
use App\Models\Mahasiswa_lokasi;
use DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Validator;

class MhsprofileController extends Controller
{    
    public function index()
    {  
        return view('profile.index');
    }
    
    public function data()
    {
        $sp = Satuanpendidikan::get();
        $profile = User::where('email',Auth::user()->email)->first();
        $mahasiswa = Mahasiswa::where("email", Auth::user()->email)->first();
        return view('profile.data',compact('mahasiswa','profile','sp'));
    }

    public function prosesuploadpoto(Request $request){
        $validator = Validator::make($request->all(), [
            'file_upload' => 'required|mimes:jpeg,png|max:2048', // Adjust file types and size limit
        ], array(
            'file_upload.required' => 'File harus diunggah.',
            'file_upload.mimes' => 'Format file tidak valid. Hanya diperbolehkan: jpeg, png.',
            'file_upload.max' => 'Ukuran file tidak boleh lebih dari 2MB.',
        ));
        $validator->after(function ($validator) use ($request) {
            //
        });
        
        if (!$validator->fails()) {
            $data=[
                //nodata
             ];
            // Check if a new file is uploaded
            if ($request->hasFile('file_upload')) {
                // Get the old file information
                $oldData = User::find(Auth::user()->id);
                
                // Delete the old file if it exists
                if ($oldData && $oldData->image) {
                    Storage::delete('public/photo/'.$oldData->image);
                }
    
                // Store the new file
                $file = $request->file('file_upload');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('public/photo/', $fileName);
                $data['image'] = $fileName;
            }             
            $where = ['email'=>Auth::user()->email];
            $eksekusi = User::where($where)->update($data);
            
            return response()->json(['success'=>true,'message'=>'Poto berhasil diubah.. silahkan refresh halaman!'], 200);

        } else {
			$errors = $validator->errors();
            foreach($errors->all() as $error){
                $er[] = $error;
            }
            $messages = implode(", ",$er);
        }
        return response()->json(['success'=>false,'error'=>$validator->errors(),'message'=>'Poto gagal di upload'], 200);
    }

    public function uploadpoto(){
        return view('profile.uploadpoto');
    }

    public function getPoto()
    {
        //$path = storage_path('app/public/photo/' . $filename);
        //return response()->file($path);
        $userId = Auth::id();
        $user = User::find($userId);
        
        if ($user && $user->image) {
            // Mendapatkan path file yang valid
            $filePath = storage_path('app/public/photo/' . $user->image);
            
            // Periksa apakah file ada
            if (file_exists($filePath)) {
                return response()->file($filePath);
            }
        }
        // Path gambar default jika file tidak ditemukan
        return response()->file('assets/img/avatars/1.png');
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'nim' => 'required',
            'prodi' => 'required', // Validasi numerik
            'tahun_masuk' => 'required|numeric',
            'phone' => 'required',
        ], [
            'nama.required' => 'nama harus di isi.',
            'nim.required' => 'nim harus di isi.',
            'prodi.required' => 'prodi harus di isi.',
            'tahun_masuk.numeric' => 'tahun masuk harus berupa angka.',
            'tahun_masuk.required' => 'tahun _masuk harus di isi.',
            'phone.required' => 'phone harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            //
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200); 
        }

        // Jika validasi berhasil, lanjutkan dengan menyimpan data ke dalam database
        $dataup = Mahasiswa::where("email",Auth::user()->email)->first(); 
        if (!$dataup) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $dataup->nama = $request->nama; // Update data
        $dataup->nim = $request->nim; // Update data
        $dataup->kodept = $request->kodept; // Update data
        $dataup->prodi = $request->prodi; // Update data
        $dataup->phone = $request->phone; // Update data
        $dataup->tahun_masuk = $request->tahun_masuk; // Update data
        $dataup->save();

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil disimpan'
        ], 200);
    }

    public function formlokasi(){
        // $locationPrograms = LokasiProgram::get();
        $desa =  Kecamatan::get();
        $data=[
            'desa'=>$desa,
            // 'locationPrograms'=>$locationPrograms
        ];
        return view('profile.lokasi',$data);
    }

    public function setlokasi(Request $request){
        $id_desa = $request->id_desa;
        $tahun = $request->tahun;

        $user = User::where("email",Auth::user()->email)->first();
        $exists = Mahasiswa_lokasi::where("id_mahasiswa",$user->mahasiswa->id_mahasiswa)->where('tahun',$tahun)->exists();
        $datain = [
            'tahun'=>$tahun,
            'id_mahasiswa'=>$user->mahasiswa->id_mahasiswa,
            'id_desa'=>$id_desa,
            'user_in_up'=>Auth::user()->email,
        ];
        if ($exists) {
            // Update the existing record
            Mahasiswa_lokasi::where("id_mahasiswa", $user->mahasiswa->id_mahasiswa)
                            ->where('tahun', $tahun)
                            ->update($datain);
            $message = 'Lokasi berhasil diperbarui';
        } else {
            // Insert a new record
            Mahasiswa_lokasi::create($datain);
            $message = 'Lokasi berhasil disimpan';
        }
    
        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => $message
        ], 200);
    }
}