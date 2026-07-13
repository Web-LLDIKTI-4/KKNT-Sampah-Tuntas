<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kecamatan;
use App\Models\Desa;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;

class KecamatanController extends Controller
{    
    public function index()
    {  
        return view('kecamatan.index');
    }
    public function listdata()
    {
        return view('kecamatan.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Kecamatan::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('kecamatan/edit/'.$row->id_kecamatan), $row->id_kecamatan);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function tambah(){
        return view('kecamatan.tambah');
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kecamatan' => [
                'required',
                function($attribute, $value, $fail) {
                    if (!isset($value) || $value === '' || $value === null) {
                        $fail('Nama kecamatan harus diisi.');
                    }
                },
            ],
        ], [
            'kecamatan.required' => 'Nama kecamatan harus diisi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Kecamatan::where("kecamatan",$request->kecamatan)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('kecamatan', 'Data sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'kecamatan'=>$request->kecamatan,
        ];
        Kecamatan::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $kecamatan = Kecamatan::where("id_kecamatan",$request->id_kecamatan)->first();
        $data=[
            'data'=>$kecamatan,
        ];
        return view('kecamatan.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kecamatan'     => 'required',
        ], [
            'kecamatan.required' => 'Nama kecamatan harus isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Kecamatan::where("kecamatan",$request->kecamatan)
            ->where("id_kecamatan","!=",$request->id_kecamatan)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('kecamatan', 'Data sudah ada!');
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
        $kecamatan = Kecamatan::find($request->id_kecamatan); // Temukan data berdasarkan id
        if (!$kecamatan) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $data=[
            'kecamatan'=>$request->kecamatan,
        ];
        $kecamatan->update($data);

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_kecamatan')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_kecamatan;
            //cek apakah sudah di gunakan di relasi lain
            $cekdata = Desa::where("id_kecamatan",$request->id_kecamatan)->exists();
            if($cekdata){
                return response()->json(['error' => 'Data gagal dihapus karena terkait dengan data lain'], 200);
            }else{
                Kecamatan::find($id)->delete();
                 // Beri respons berhasil
                return response()->json(['message' => 'Data berhasil dihapus'], 200);
            }
           
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}