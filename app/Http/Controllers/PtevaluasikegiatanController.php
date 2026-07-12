<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Evaluasikegiatan;
use DB;
use Validator;

class PtevaluasikegiatanController extends Controller
{    
    public function index()
    {  
        return view('user.evaluasikegiatan.index');
    }
    public function tambah(){
        // Retrieve all instances of Evaluasikegiatan
        $evaluasiList = Evaluasikegiatan::all();

        // Prepare an array to hold the result
        $result = [];

        // Get the current user email and current year
        $userEmail = Auth::user()->email;
        $year = date('Y');

        // Loop through each evaluasi
        foreach ($evaluasiList as $evaluasi) {
            // Get the specific jawaban for the user and year
            $jawabanevaluasi = $evaluasi->jawabanPeruserTahun($userEmail, $year)->first();

            // Include both evaluasi and jawabanevaluasi in the result
            $result[] = [
                'evaluasi' => $evaluasi,
                'jawaban' => $jawabanevaluasi,
            ];
        }
        // Pass the data to the view
        $data = [
            'evaluasi' => $result,
        ];

        return view('user.evaluasikegiatan.tambah', $data);
    }
    public function insert(Request $request){
        $data = $request->jawaban; // This will be an array with evaluation IDs as keys

        foreach ($data as $id_evaluasi => $jawaban) {
            // Find the existing evaluasi or create a new one
            $evaluasi = Evaluasikegiatanjawaban::firstOrNew(['id_evaluasi' => $id_evaluasi, 'user' => Auth::user()->email]);
            
            // Update the jawaban
            $evaluasi->jawaban = $jawaban;
            $evaluasi->tahun = date('Y');
            $evaluasi->kodept = Auth::user()->email;
            $evaluasi->save();
        }
        return response()->json(['success'=>true,'message'=>'Data berhasil disimpan!'], 200);
    }
}