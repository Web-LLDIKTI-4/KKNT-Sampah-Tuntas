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

class DesaController extends Controller
{    
    public function index()
    {  
        return view('desa.index');
    }
    public function listdata()
    {
        return view('desa.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Desa::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('kecamatan', function($row){
                    return $row->kecamatan->kecamatan;
                })
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('desa/edit/'.$row->id_desa), $row->id_desa);
                })
                ->rawColumns(['action','kecamatan'])
                ->make(true);
        }
    }
    public function tambah(){
        $kecamatan = Kecamatan::get();
        return view('desa.tambah',compact('kecamatan'));
    }
    public function insert(Request $request)
    {
               
        $validator = Validator::make($request->all(), [
            'id_kecamatan' => [
                'required',
                function($attribute, $value, $fail) {
                    if (!isset($value) || $value === '' || $value === null) {
                        $fail('Nama kecamatan harus dipilih.');
                    }
                },                
            ],
            'desa'     => 'required',
        ], [
            'id_kecamatan.required' => 'Nama kecamatan harus dipilih.',
            'desa.required' => 'Nama desa harus isi.',
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = Desa::where("desa",$request->desa)
            ->where("id_kecamatan",$request->id_kecamatan)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('desa', 'Data sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'id_kecamatan'=>$request->id_kecamatan,
            'desa'=>$request->desa,
        ];
        Desa::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $desa = Desa::where("id_desa",$request->id_desa)->first();
        $kecamatan = Kecamatan::get();
        $data=[
            'data'=>$desa,
            'kecamatan'=>$kecamatan,
        ];
        return view('desa.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'desa'     => 'required',
        ], [
            'desa.required' => 'Nama desa harus isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Desa::where("desa",$request->desa)
            ->where("id_kecamatan",$request->id_kecamatan)
            ->where("id_desa","!=",$request->id_desa)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('desa', 'Data sudah ada!');
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
        $desa = Desa::find($request->id_desa); // Temukan data berdasarkan id
        if (!$desa) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $data=[
            'id_kecamatan'=>$request->id_kecamatan,
            'desa'=>$request->desa,
        ];
        $desa->update($data);

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_desa')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_desa;
            Desa::find($id)->delete();
            // Beri respons berhasil
           return response()->json(['message' => 'Data berhasil dihapus'], 200);           
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}