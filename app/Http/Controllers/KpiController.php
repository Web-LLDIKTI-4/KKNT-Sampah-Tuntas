<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;

class KpiController extends Controller
{    
    public function index()
    {  
        return view('kpi.index');
    }
    public function listdata()
    {
        return view('kpi.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Kpi::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('kpi/edit/'.$row->id_kpi), $row->id_kpi);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function tambah(){
        return view('kpi.tambah');
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kpi'     => 'required|unique:kpi,nama_kpi'
        ], [
            'nama_kpi.required' => 'Key performance indicator harus di isi',
            'nama_kpi.unique' => 'Key performance indicator sudah ada!'
        ]);
        /*
        $validator->after(function($validator) use ($request) {
            $cekdata = Kpi::where("kpi",$request->kpi)->get();
            if ($cekdata > 0) {
                $validator->errors()->add('kpi', 'Key performance indicator sudah ada!');
            }
        });
        */
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }

         // Jika validasi berhasil, lanjutkan dengan menyimpan data ke dalam database
        $kpi = new Kpi();
        $kpi->nama_kpi = $request->nama_kpi;
        $kpi->save();
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Key performance indicator berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $data = Kpi::where("id_kpi",$request->id_kpi)->first();
        return view('kpi.edit',compact('data'));
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kpi' => 'required'
        ], [
            'nama_kpi.required' => 'Key performance indicator harus di isi'
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = Kpi::where("nama_kpi", $request->nama_kpi)
                        ->where("id_kpi", "!=", $request->id_kpi)
                        ->exists(); // Menggunakan exists() untuk mengecek keberadaan data

            if ($cekdata) {
                $validator->errors()->add('nama_kpi', 'Key performance indicator sudah digunakan!');
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
        $kpi = Kpi::find($request->id_kpi); // Temukan data berdasarkan id
        if (!$kpi) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $kpi->nama_kpi = $request->nama_kpi; // Update data
        $kpi->save();

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Key performance indicator berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_kpi')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_kpi;
            //cek apakah sudah di gunakan di relasi lain
            $cek_kpicapaian = Kpicapaian::where("id_kpi",$id)->exists();
            $cek_kpitarget = Kpitarget::where("id_kpi",$id)->exists();
            if($cek_kpicapaian || $cek_kpitarget){
                return response()->json(['error' => 'Hapus dulu data terkait'], 400);
            }else{
                Kpi::find($id)->delete();    
                // Beri respons berhasil
                return response()->json(['message' => 'Data berhasil dihapus'], 200);
            }
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}