<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Kpitarget;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;

class KpitargetController extends Controller
{    
    public function index()
    {  
        return view('kpitarget.index');
    }
    public function listdata()
    {
        return view('kpitarget.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Kpitarget::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_kpi', function($row){
                    return $row->kpi->nama_kpi;
                })
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('kpitarget/edit/'.$row->id_target), $row->id_target);
                })
                ->rawColumns(['action','nama_kpi'])
                ->make(true);
        }
    }
    public function tambah(){
        $data=[
            'kpi'=>Kpi::get(),
        ];
        return view('kpitarget.tambah',$data);
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kpitarget'     => 'required',
            'tahapan'     => 'required',
            'persen'     => 'required|numeric',
        ], [
            'nama_kpitarget.required' => 'Target Key performance indicator harus diisi.',
            'tahapan.required' => 'Tahapan KPI harus diisi.',
            'persen.required' => 'Persen harus diisi.',
            'persen.numeric' => 'Persen harus angka.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Kpitarget::where("id_kpi",$request->id_kpi)
            ->where("nama_kpitarget",$request->nama_kpitarget)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('nama_kpitarget', 'Target Key performance indicator sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'id_kpi'=>$request->id_kpi,
            'tahapan'=>$request->tahapan,
            'nama_kpitarget'=>$request->nama_kpitarget,
            'persen'=>$request->persen,
        ];
        Kpitarget::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Key performance indicator berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $kpi = Kpi::get();
        $kpitarget = Kpitarget::where("id_target",$request->id_target)->first();
        $data=[
            'kpi'=>$kpi,
            'data'=>$kpitarget,
        ];
        return view('kpitarget.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kpitarget'     => 'required',
            'tahapan'     => 'required',
            'persen'     => 'required|numeric',
        ], [
            'nama_kpitarget.required' => 'Target Key performance indicator harus diisi.',
            'tahapan.required' => 'Tahapan KPI harus diisi.',
            'persen.required' => 'Persen harus diisi.',
            'persen.numeric' => 'Persen harus angka.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Kpitarget::where("id_kpi",$request->id_kpi)
            ->where("nama_kpitarget",$request->nama_kpitarget)
            ->where("id_target","!=",$request->id_target)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('nama_kpitarget', 'Target Key performance indicator sudah ada!');
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
        $kpitarget = kpitarget::find($request->id_target); // Temukan data berdasarkan id
        if (!$kpitarget) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $data=[
            'id_kpi'=>$request->id_kpi,
            'tahapan'=>$request->tahapan,
            'nama_kpitarget'=>$request->nama_kpitarget,
            'persen'=>$request->persen,
        ];
        $kpitarget->update($data);

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Key performance indicator berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_target')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_target;
            //cek apakah sudah di gunakan di relasi lain
            
            Kpitarget::find($id)->delete();
    
            // Beri respons berhasil
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}