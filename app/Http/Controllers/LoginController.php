<?php  
namespace App\Http\Controllers;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\LokasiProgram;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{    
	public function index()
	{
		$kodeptDpl = DB::table('users')
			->join('dpl', 'dpl.email', '=', 'users.email')
			->whereNotNull('users.location_program')
			->whereNotNull('dpl.kodept')
			->select('users.location_program', 'dpl.kodept');

		$kodeptMahasiswa = DB::table('users')
			->join('mahasiswa', 'mahasiswa.email', '=', 'users.email')
			->whereNotNull('users.location_program')
			->whereNotNull('mahasiswa.kodept')
			->select('users.location_program', 'mahasiswa.kodept');

		$union = $kodeptDpl->unionAll($kodeptMahasiswa);

		$jumlahPtPerLokasi = DB::table(DB::raw("({$union->toSql()}) as gabungan"))
			->mergeBindings($union)
			->select('location_program', DB::raw('COUNT(DISTINCT kodept) as total'))
			->groupBy('location_program')
			->pluck('total', 'location_program');

		$lokasiProgramList = LokasiProgram::query()
			->withCount([
				'users as jumlah_dpl' => fn ($q) => $q->where('role', 'dpl'),
				'users as jumlah_mahasiswa' => fn ($q) => $q->where('role', 'mahasiswa'),
			])
			->orderBy('nama_lokasi')
			->get()
			->map(function ($lokasi) use ($jumlahPtPerLokasi) {
				$lokasi->jumlah_pt = $jumlahPtPerLokasi[$lokasi->id] ?? 0;
				return $lokasi;
			});

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
				$user = Auth::user();

				if (in_array($user->role, ['dpl', 'mahasiswa', 'pt'])) {
					$lokasi = trim((string) $request->input('lokasi'));

					if ($lokasi === '') {
						Auth::logout();
						$ret['messages'] = "Silakan pilih lokasi program terlebih dahulu";
						return response()->json($ret);
					}

					if (!$user->location_program) {
						Auth::logout();
						$ret['messages'] = "Anda belum memiliki lokasi program kkn, silahkan hubungi admin untuk menambahkan lokasi program anda!";
						return response()->json($ret);
					}

					if (!$user->locationProgram()->where('nama_lokasi', $lokasi)->exists()) {
						Auth::logout();
						$ret['messages'] = "Lokasi program anda tidak valid!";
						return response()->json($ret);
					}

					// samakan penulisan dengan data master jika ditemukan
					$lokasiMaster = LokasiProgram::whereRaw('LOWER(nama_lokasi) = ?', [mb_strtolower($lokasi)])
						->value('nama_lokasi');
					$lokasi = $lokasiMaster ?: $lokasi;

					session(['lokasi_program' => $lokasi]);
					$ret['redirect_url'] = url('home/'.rawurlencode(mb_strtolower($lokasi)));
				} else {
					session()->forget('lokasi_program');
					$ret['redirect_url'] = url('home');
				}

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