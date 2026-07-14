<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Dpl;
use App\Models\User;
use App\Models\Satuanpendidikan;
use DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Validator;

class ProfileController extends Controller
{    
    public function index()
    {  
        return view('profile.index');
    }
    public function data()
    {
        $sp = Satuanpendidikan::orderByRaw("TRIM(nm_lemb) DESC")->get();
        $profile = User::where('email',Auth::user()->email)->first();
        $dpl = Dpl::where("email",Auth::user()->email)->first();
        return view('profile.data',compact('profile','dpl','sp'));
    }
    public function uploadpoto(){
        return view('profile.uploadpoto');
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
        if (Auth::user()->role == "admin") {
            $validator = Validator::make($request->all(), [
                'nama' => 'required',
            ], [
                'nama.required' => 'Nama harus di isi.',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'nama' => 'required',
                'nidn' => 'required',
                'prodi' => 'required',
                'phone' => 'required',
            ], [
                'nama.required' => 'Nama harus di isi.',
                'nidn.required' => 'NIDN harus di isi.',
                'prodi.required' => 'Prodi harus di isi.',
                'phone.required' => 'Nomor telepon harus di isi.',
            ]);
        }
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200); 
        }
        
        // Pastikan data ditemukan sebelum pembaruan
        $dataup = User::where("email", Auth::user()->email)->first(); 
        if (!$dataup) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); 
        }
        
        // Lakukan pembaruan profil
        $dataup->name = $request->nama;
        $dataup->save();
        
        // Lakukan pembaruan atau penyisipan data DPL
        if (Auth::user()->role == "dpl") {
            $dplData = [
                'nidn' => $request->nidn,
                'nama' => $request->nama,
                'email' => Auth::user()->email,
                'prodi' => $request->prodi,
                'kodept' => $request->kodept,
                'phone' => $request->phone,
            ];
        
            $exists = Dpl::where("email", Auth::user()->email)->exists();
            if ($exists) {
                Dpl::where("email", Auth::user()->email)->update($dplData);
            } else {
                Dpl::create($dplData);
            }
        }
        
        // Tampilkan pesan sukses
        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil disimpan'
        ], 200);
    }

    
}