<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dpllaporan;
use App\Models\Logbulanan;
use App\Models\Dpl;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DpllaporanController extends Controller
{    
    public function index()
    {  
        $exists = Dpl::where("email", Auth::user()->email)->exists();
        if (!$exists) {
            return redirect(url('profile'));
        }

        $namaBulan = [];
        for ($i = 1; $i <= 12; $i++) {
            $namaBulan[$i] = Carbon::create()->month($i)->format('F');
        }
        return view('laporan.index',compact('namaBulan'));
    }
    public function tambah(Request $request)
    {
        $isi = Dpllaporan::where('email',Auth::user()->email)->where('tahun',$request->tahun)->where('bulan',$request->bulan)->first();

        $data = [
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'isi' => $isi
        ];

        return view('laporan.tambah',$data);
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
        $cekdata = Dpllaporan::where("bulan", $request->bulan)
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
            Dpllaporan::where("bulan", $request->bulan)
                ->where("tahun", $request->tahun)
                ->where("email", Auth::user()->email)
                ->update($data);
        } else {
            // Lakukan insert
            Dpllaporan::create($data);
        }
    
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Log kegiatan bulanan berhasil disimpan'], 200);
    }
    public function destroy(Request $request){
        if (Dpllaporan::where("email", Auth::user()->email)->where("id_laporan", $request->id_laporan)->delete()) {
            return response()->json(['success' => true, 'message' => 'Log kegiatan bulanan berhasil dihapus'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Log kegiatan bulanan gagal dihapus'], 200);
        }
        
    }
    public function listdata(){
        $laporan = Dpllaporan::where('email',Auth::user()->email)->get();
        return view('laporan.listdata',compact('laporan'));
    }
}