<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Logkegiatan;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;

class LogkegiatanController extends Controller
{    
    public function index()
    {  
        return view('logkegiatan.mahasiswa.index');
    }
    public function listdata()
    {
        return view('logkegiatan.mahasiswa.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Logkegiatan::where("email",Auth::user()->email)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_kpi', function($row){
                    return $row->kpi->nama_kpi;
                })
                ->addColumn('deskripsi', function($row){
                    return $row->deskripsi.'<br><a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                })
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('logkegiatan/edit/'.$row->id_log), $row->id_log);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function tambah(){
        $kpi = Kpi::get();
        return view('logkegiatan.mahasiswa.tambah',compact('kpi'));
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required',
            'tanggal' => 'required',
            'volume' => 'required|numeric', // Validasi numerik
            'satuan' => 'required',
        ], [
            'deskripsi.required' => 'Deskripsi harus di isi.',
            'tanggal.required' => 'Tanggal harus di isi.',
            'volume.required' => 'Volume harus di isi.',
            'volume.numeric' => 'Volume harus berupa angka.',
            'satuan.required' => 'Satuan harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Logkegiatan::where("tanggal",$request->tanggal) //where("deskripsi",$request->deskripsi)
                        ->where("email",Auth::user()->email)
                        ->count(); // Menghitung jumlah baris yang ditemukan
            
            if ($cekdata > 0) {
                $validator->errors()->add('tanggal', 'Kegiatan tanggal '.$request->tanggal.' sudah ada!');
            }
        });
       
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }

         // Jika validasi berhasil, lanjutkan dengan menyimpan data ke dalam database
         $data =[
            'email'=>Auth::user()->email,
            'tanggal'=>$request->tanggal,
            'deskripsi'=>$request->deskripsi,
            'volume'=>$request->volume,
            'satuan'=>$request->satuan,
            'id_kpi'=>$request->id_kpi,
            'tautan'=>$request->tautan,
        ];
         Logkegiatan::insert($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Log kegiatan berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $data = Logkegiatan::where("id_log",$request->id_log)->first();
        $kpi = Kpi::get();
        $data=['data'=>$data,'kpi'=>$kpi];
        return view('logkegiatan.mahasiswa.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required',
            'tanggal' => 'required',
            'volume' => 'required|numeric', // Validasi numerik
            'satuan' => 'required',
        ], [
            'deskripsi.required' => 'Deskripsi harus di isi.',
            'tanggal.required' => 'Tanggal harus di isi.',
            'volume.required' => 'Volume harus di isi.',
            'volume.numeric' => 'Volume harus berupa angka.',
            'satuan.required' => 'Satuan harus di isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Logkegiatan::where("deskripsi",$request->deskripsi)
                        ->where("tanggal",$request->tanggal)
                        ->where("email",Auth::user()->email)
                        ->where("id_log", "!=", $request->id_log)
                        ->exists(); // Menggunakan exists() untuk mengecek keberadaan data
            
            if ($cekdata) {
                $validator->errors()->add('deskripsi', 'Deskripsi sudah digunakan!');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200); 
        }

        // Jika validasi berhasil, lanjutkan dengan menyimpan data ke dalam database
        $dataup = Logkegiatan::find($request->id_log); // Temukan data berdasarkan id
        if (!$dataup) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $dataup->tanggal = $request->tanggal; // Update data
        $dataup->deskripsi = $request->deskripsi; // Update data
        $dataup->volume = $request->volume; // Update data
        $dataup->satuan = $request->satuan; // Update data
        $dataup->id_kpi = $request->id_kpi; // Update data
        $dataup->tautan = $request->tautan; // Update data
        $dataup->save();

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Log Kegiatan berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_log')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_log;
            //cek apakah sudah di gunakan di relasi lain
            
            Logkegiatan::find($id)->delete();
    
            // Beri respons berhasil
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}