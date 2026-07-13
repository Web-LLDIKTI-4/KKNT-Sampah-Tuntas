<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Pjdesa;
use App\Models\User;
use App\Models\Kpicapaian;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;

class PjdesaController extends Controller
{    
    public function index()
    {  
        return view('pjdesa.index');
    }
    public function listdata()
    {
        return view('pjdesa.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Pjdesa::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('kecamatan', function($row){
                    return $row->desa->kecamatan->kecamatan ?? '';
                })
                ->addColumn('desa', function($row){
                    return $row->desa->desa ?? '';
                })
                ->addColumn('pjdesa', function($row){
                    return $row->mahasiswa->nama ?? 'Data tidak tersedia';
                })
                ->addColumn('instansi', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia';
                })
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('pjdesa/edit/'.$row->id_pjdesa), $row->id_pjdesa);
                })
                ->rawColumns(['action','kecamatan'])
                ->make(true);
        }
    }
    public function tambah(){
        $kecamatan = Kecamatan::get();
        $user = User::where("akses","pjdesa")->get();
        $data=[
            'kecamatan'=>$kecamatan,
            'user'=>$user,
        ];
        return view('pjdesa.tambah',$data);
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_desa'     => 'required',
            'email'     => 'required',
        ], [
            'id_desa.required' => 'Desa harus dipilih.',
            'email.required' => 'PJ Desa harus dipilih.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Pjdesa::where("email",$request->email)
            ->where("id_desa",$request->id_desa)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('email', 'Data sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'id_desa'=>$request->id_desa,
            'email'=>$request->email,
        ];
        Pjdesa::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $pjdesa = Pjdesa::where("id_pjdesa",$request->id_pjdesa)->first();
        $kecamatan = Kecamatan::get();
        $user = User::where("akses","pjdesa")->get();
        $data=[
            'data'=>$pjdesa,
            'kecamatan'=>$kecamatan,
            'user'=>$user,
        ];
        return view('pjdesa.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_desa'     => 'required',
            'email'     => 'required',
        ], [
            'id_desa.required' => 'Desa harus dipilih.',
            'email.required' => 'PJ Desa harus dipilih.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Pjdesa::where("email",$request->email)
            ->where("id_desa",$request->id_desa)
            ->where("id_pjdesa","!=",$request->id_pjdesa)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('email', 'Data sudah ada!');
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
        $pjdesa = Pjdesa::find($request->id_pjdesa); // Temukan data berdasarkan id
        if (!$pjdesa) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }

        $data=[
            'id_desa'=>$request->id_desa,
            'email'=>$request->email,
        ];
        $pjdesa->update($data);

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_pjdesa')) {
            $id = $request->id_pjdesa;
            $pjdesa = Pjdesa::where("id_pjdesa",$id)->first();
            //cek dulu apakah sudah ada capaian
            $exists = Kpicapaian::where("email",$pjdesa->email)->exists();
            if($exists){
                return response()->json(['error' => 'Data tidak dapat di hapus karena terkait dengan data capaian'], 400);    
            }else{
                // Lakukan tindakan penghapusan di sini
                Pjdesa::find($id)->delete();
                // Beri respons berhasil
                return response()->json(['message' => 'Data berhasil dihapus'], 200);    
            }                 
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}