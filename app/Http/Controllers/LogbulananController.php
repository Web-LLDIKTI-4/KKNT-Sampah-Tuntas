<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Logkegiatan;
use App\Models\Logbulanan;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LogbulananController extends Controller
{    
    public function index()
    {  
        $namaBulan = [];
        for ($i = 1; $i <= 12; $i++) {
            $namaBulan[$i] = Carbon::create()->month($i)->format('F');
        }
        return view('logbulanan.mahasiswa.index',compact('namaBulan'));
    }

    public function tambah(Request $request)
    {
        $isi = Logbulanan::where('email',Auth::user()->email)->where('tahun',$request->tahun)->where('bulan',$request->bulan)->first();
        $logharian = Logkegiatan::where('email', Auth::user()->email)
        ->whereYear('tanggal', $request->tahun)
        ->whereMonth('tanggal', $request->bulan)
        ->get();
        $data = [
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'isi' => $isi,
            'logharian' => $logharian
        ];

        return view('logbulanan.mahasiswa.tambah',$data);
    }
    
    public function insert(Request $request){
        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required',
            'bulan' => 'required',
            'tahun' => 'required',
        ], [
            'deskripsi.required' => 'Deskripsi harus di isi.',
            'bulan.required' => 'Bulan harus di isi.',
            'tahun.required' => 'Tahun harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $plainText = strip_tags($request->deskripsi);
            $wordCount = str_word_count($plainText);
           
            if ($wordCount < 200) {
                $validator->errors()->add('deskripsi', 'Deskripsi minimal 200 kata!');
            }
        });
       
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $cekdata = Logbulanan::where("bulan", $request->bulan)
        ->where("tahun", $request->tahun)
        ->where("email", Auth::user()->email)
        ->count(); // Menghitung jumlah baris yang ditemukan
    
        $data = [
            'email' => Auth::user()->email,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'tautan' => $request->tautan,
            'deskripsi' => $request->deskripsi
        ];
        
        if ($cekdata > 0) {
            // Lakukan update
            Logbulanan::where("bulan", $request->bulan)
                ->where("tahun", $request->tahun)
                ->where("email", Auth::user()->email)
                ->update($data);
        } else {
            // Lakukan insert
            Logbulanan::create($data);
        }
    
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Log kegiatan bulanan berhasil disimpan'], 200);
    }

    public function destroy(Request $request){
        if (Logbulanan::where("email", Auth::user()->email)->where("id_logbulanan", $request->id_logbulanan)->delete()) {
            return response()->json(['success' => true, 'message' => 'Log kegiatan bulanan berhasil dihapus'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Log kegiatan bulanan gagal dihapus'], 200);
        }
        
    }

    public function listdata(){
        $laporan = Logbulanan::where('email',Auth::user()->email)->get();
        return view('logbulanan.mahasiswa.listdata',compact('laporan'));
    }
}