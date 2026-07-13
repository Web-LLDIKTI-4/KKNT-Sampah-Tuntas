<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dplmentoring;
use App\Models\Logbulanan;
use App\Models\Mahasiswa;
use App\Models\Freeform;
use App\Models\User;
use App\Models\Nilaikonversi;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class NilaifreeformController extends Controller
{    
    public function index()
    {  
        $user = User::where("email",Auth::user()->email)->first();
        $mahasiswa = Mahasiswa::where("id_mahasiswa",$user->mahasiswa->id_mahasiswa)->first();
        if (!$mahasiswa || !$mahasiswa->user || !$mahasiswa->user->email) {
            // Redirect or handle the case where the mahasiswa or email is not available
            return redirect('nilaifreeform');
        }
        $data=[
            'mahasiswa'=>$mahasiswa,
        ];
        return view('nilaifreeform.index',$data);
    }
   
    public function nilaikonversi(){
        $user = User::where("email",Auth::user()->email)->first();

        $mahasiswa = Mahasiswa::where("id_mahasiswa",$user->mahasiswa->id_mahasiswa)->first();
        $nilai = Nilaikonversi::where("id_mahasiswa",$user->mahasiswa->id_mahasiswa)->get();
        $data=[
            'mahasiswa'=>$mahasiswa,
            'nilai'=>$nilai,
        ];
        return view('nilaifreeform.nilaikonversi',$data);
    }
    public function freeform(){
        $user = User::where("email",Auth::user()->email)->first();

        $mahasiswa = Mahasiswa::where("id_mahasiswa",$user->mahasiswa->id_mahasiswa)->first();
        $nilai = Freeform::where("id_mahasiswa",$user->mahasiswa->id_mahasiswa)->get();
        $data=[
            'mahasiswa'=>$mahasiswa,
            'nilai'=>$nilai,
        ];
        return view('nilaifreeform.freeform',$data);
    }

}