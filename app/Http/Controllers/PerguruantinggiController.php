<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Satuanpendidikan;
use DataTables;
use Illuminate\Support\Facades\Validator;

class PerguruantinggiController extends Controller
{    
    public function index()
    {  
        return view('perguruantinggi.index');
    }
    public function listdata()
    {
        return view('perguruantinggi.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Satuanpendidikan::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-pencil-square"></i></a> <a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-trash"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function getdata(){
        $kodept=false;
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'https://pdpt.lldikti4.id/api/rsatuanpendidikan/splldikti4/format/json');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array(
            'kodept' => $kodept
        ));
            
        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);
            
        $result = json_decode($buffer);
        if(!$result){
            return response()->json(['success'=>false,'messages'=>'Koneksi ke PDDIKTI error!','errors' =>'Koneksi ke PDDIKTI error!'], 200);
        }else{
            $jumlahup=0;
            $jumlahin=0;
            foreach($result as $key=>$val){		
                $data = (array) $val;			
                $cekdata = Satuanpendidikan::where("npsn", $val->npsn)->where("id_sp", $val->id_sp)->exists();
                if ($cekdata) {
                    $update = Satuanpendidikan::where("npsn", $val->npsn)->where("id_sp", $val->id_sp)->update($data);
                    if ($update) {
                        $jumlahup++;
                    } 
                } else {
                    $insert = Satuanpendidikan::create($data);
                    if ($insert) {
                        $jumlahin++;
                    }
                }
            }
            return response()->json(['success'=>true,'messages'=>''.$jumlahup.' Data berhasil di update dan '.$jumlahin.' Data berhasil disimpan!'], 200);
        }			
		
    }
    public function tambah(){
        return view('perguruantinggi.tambah');
    }
    public function insert(Request $request){
        $validator = Validator::make($request->all(), [
            'kodept'     => 'required|unique:ref_satuanpendidikan,npsn'
        ], [
            'kodept.required' => 'Kode perguruan tinggi harus di isi',
            'kodept.unique' => 'Kode perguruan tinggi sudah ada!'
        ]);
     
        if ($validator->fails()) {
            return response()->json(['success'=>false,'messages'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }

        $kodept = $request->kodept;
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'https://pdpt.lldikti4.id/api/rsatuanpendidikan/satuanpendidikanall/format/json');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array(
            'kodept' => $kodept, 'status' => 'A'
        ));
            
        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);			 
        $result = json_decode($buffer);

        
        $datain = $result;
        if(!$datain){
            return response()->json(['success' => false, 'messages' => 'Koneksi ke PDDIKTI error'], 200);
        }

        $cekdata = Satuanpendidikan::where("npsn", $datain->npsn)->where("id_sp", $datain->id_sp)->exists();
        if ($cekdata) {
            $update = Satuanpendidikan::where("npsn", $datain->npsn)->where("id_sp", $datain->id_sp)->update((array) $datain);

            if ($update) {
                return response()->json(['success' => true, 'messages' => 'Data perguruan tinggi berhasil diupdate'], 200);
            } else {
                return response()->json(['success' => false, 'messages' => 'Gagal memperbarui data perguruan tinggi'], 500);
            }
        } else {
            $insert = Satuanpendidikan::create((array) $datain);

            if ($insert) {
                return response()->json(['success' => true, 'messages' => 'Data perguruan tinggi berhasil dimasukkan'], 200);
            } else {
                return response()->json(['success' => false, 'messages' => 'Gagal memasukkan data perguruan tinggi'], 500);
            }
        }

		
    }
      
}
