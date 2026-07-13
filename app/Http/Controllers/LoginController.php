<?php  
namespace App\Http\Controllers;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\LokasiProgram;

class LoginController extends Controller
{    
	public function index(){
		$lokasiProgramList = LokasiProgram::query()
			->orderBy('nama_lokasi')
			->pluck('nama_lokasi');

		return view('login', compact('lokasiProgramList'));
	}
    public function proseslogin(Request $request){
        $ret=array('success'=>false,'messages'=>array());
		$validator = Validator::make($request->all(), [
            'username' => 'required',
			'password' => 'required'
        ], array(
            'username.required' => 'Username harus diisi',
			'password.required' => 'Password harus diisi',
        ));

		$username = $request->username;
		$password = $request->password;
        if (!$validator->fails()) {
            $data = [
				'email' => $username,
				'password' => $password,
			];
	
			if (Auth::Attempt($data)) {
				$ret['messages'] = "proses login...";
				$ret['success'] = true;
				User::where('email',$username)->update(['last_login'=>date("Y-m-d H:i:s")]);
			}else{
				$ret['messages'] = "Email atau Password Salah";
			}
        } else {
			$errors = $validator->errors();
            foreach($errors->all() as $error){
                $er[] = $error;
            }
            $ret['messages'] = implode(", ",$er);
        }
		return response()->json($ret);

    }
    public function logout(){
		//delete_cookie('userinfo_cookies','lldikti4.or.id');//'lldikti4.or.id'
		$cookie = \Cookie::forget('userinfo_cookies');
		session()->flush("userinfo");	
		return redirect('/');
	}

	public function createuser(){
		$user = User::create([
            'email' =>"admin@gmail.com",
			'name' => "Idik Nursidik",
            'password' => Hash::make("rahasia"),
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ]);

	}
    
}