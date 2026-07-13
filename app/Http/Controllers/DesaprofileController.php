<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Desaprofile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class DesaprofileController extends Controller
{    
    public function index()
    {  
        return view('desaprofile.index');
    }
    public function listdata()
    {
        return view('desaprofile.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Desaprofile::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('desa', function($row){
                    return $row->desa->desa;
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="#modalku" data-bs-toggle="modal" class="modalButton p-0 m-0" data-src="'.url('desaprofile/edit/'.$row->id_profile).'" title="Edit Data"><i class="ri-edit-box-line text-success"></i></a> <a href="javascript:void(0)" id="hapus_'.$row->id_profile.'"  class="p-0 m-0"><i class="ri-delete-bin-3-line text-danger"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action','kecamatan'])
                ->make(true);
        }
    }
    public function tambah(){
        $kecamatan = Kecamatan::get();
        return view('desaprofile.tambah',compact('kecamatan'));
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'potensi'     => 'required',
            'masalah'     => 'required',
        ], [
            'potensi.required' => 'Potensi desa harus isi.',
            'masalah.required' => 'Masalah desa harus isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Desaprofile::where("id_desa",$request->id_desa)
            ->where("tahun",$request->tahun)
            ->where("potensi",$request->potensi)
            ->where("masalah",$request->masalah)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('potensi', 'Data potensi dan masalah yang sama sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'id_desa'=>$request->id_desa,
            'tahun'=>$request->tahun,
            'potensi'=>$request->potensi,
            'masalah'=>$request->masalah,
        ];
        Desaprofile::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $desa = Desaprofile::where("id_profile",$request->id_profile)->first();
        $kecamatan = Kecamatan::get();
        $data=[
            'data'=>$desa,
            'kecamatan'=>$kecamatan,
        ];
        return view('desaprofile.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'potensi'     => 'required',
            'masalah'     => 'required',
        ], [
            'potensi.required' => 'Potensi desa harus isi.',
            'masalah.required' => 'Masalah desa harus isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Desaprofile::where("id_desa",$request->id_desa)
            ->where("tahun",$request->tahun)
            ->where("potensi",$request->potensi)
            ->where("masalah",$request->masalah)
            ->where("id_profile","!=",$request->id_profile)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('potensi', 'Data potensi dan masalah yang sama sudah ada!');
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
        $desaprofile = Desaprofile::find($request->id_profile); // Temukan data berdasarkan id
        if (!$desaprofile) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $data=[
            'id_desa'=>$request->id_desa,
            'tahun'=>$request->tahun,
            'potensi'=>$request->potensi,
            'masalah'=>$request->masalah,
        ];
        $desaprofile->update($data);

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_profile')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_profile;
            Desaprofile::find($id)->delete();
            // Beri respons berhasil
           return response()->json(['message' => 'Data berhasil dihapus'], 200);           
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}