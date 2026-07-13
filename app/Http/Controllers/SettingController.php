<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{    
    public function index()
    {  
        return view('setting');
    } 
    
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'plama' => 'required',
            'pbaru' => 'required',
            'pbaruulangi' => 'required', // Validasi numerik
        ], [
            'plama.required' => 'Password lama harus di isi.',
            'pbaru.required' => 'Password baru harus di isi.',
            'pbaruulangi.required' => 'Password baru harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = User::where("email", Auth::user()->email)->first();
            if (!$cekdata || !Hash::check($request->plama, $cekdata->password)) {
                $validator->errors()->add('plama', 'Password lama salah!');
            }
            if ($request->pbaru != $request->pbaruulangi) {
                $validator->errors()->add('pbaru', 'Password baru harus sama dengan konfirmasinya!');
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
            'password'=> Hash::make($request->pbaru)
        ];
        User::where("email",Auth::user()->email)->update($data);
        return response()->json(['success' => true,'message'=>"Akun berhasil diupdate, silahkan login ulang"]);       
    }
}
