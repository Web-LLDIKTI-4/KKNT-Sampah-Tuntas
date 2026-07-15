<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Kpitarget;
use App\Models\Kpicapaian;
use App\Models\Pjdesa;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KpicapaianController extends Controller
{    
    public function index()
    {  
        return view('kpicapaian.index');
    }
    public function listdata()
    {
        return view('kpicapaian.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Kpicapaian::where('email',Auth::user()->email)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_kpi', function($row){
                    return $row->kpi->nama_kpi;
                })
                ->addColumn('tahapan', function($row){
                    return $row->target->tahapan;
                })
                ->addColumn('nama_kpitarget', function($row){
                    return $row->target->nama_kpitarget;
                })
                ->addColumn('status_capaian', function($row){
                    if($row->status_capaian == 'Y'){
                        return '<span class="badge bg-success">Sudah Selesai</span>';
                    }elseif ($row->status_capaian == 'P') {
                        return '<span class="badge bg-warning">Proses</span>';
                    }else{
                        return '<span class="badge bg-danger">Belum Ditindaklanjuti</span>';
                    }
                })
                ->addColumn('tautan', function($row){
                    return '<a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="#modalku" data-bs-toggle="modal" class="modalButton btn btn-sm p-0 m-0" data-src="'.url('kpicapaian/edit/'.$row->id_capaian).'" title="Edit Data"><i class="fas fa-edit"></i></a> <a href="javascript:void(0)" id="hapus_'.$row->id_capaian.'"  class="btn btn-sm p-0 m-0"><i class="fa fa-trash"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action','tautan', 'status_capaian'])
                ->make(true);
        }
    }
    public function kpitarget(Request $request)
    {
        $kpitarget = Kpitarget::where("id_kpi",$request->id_kpi)->get();
        return view('kpicapaian.kpitarget',compact('kpitarget'));
    }
    public function tambah(){
        $data=[
            'kpi'=>Kpi::get(),
            'kpitarget'=> Kpitarget::get(),
        ];
        return view('kpicapaian.tambah', $data);
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tautan' => 'required',
            'permasalahan' => 'required',
            'solusi' => 'required',
            'kendala' => 'required',
            'id_kpi' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (!isset($request[$attribute]) || $value === 'null') {
                        $fail('KPI harus dipilih.');
                    }
                },
            ],
            'id_target' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (!isset($request[$attribute]) || $value === 'null') {
                        $fail('Tahapan harus dipilih.');
                    }
                },
            ],
        ], [
            'tautan.required' => 'Tatutan harus isi.',
            'permasalahan.required' => 'Permasalahan harus isi.',
            'solusi.required' => 'Solusi harus isi.',
            'kendala.required' => 'Kendala harus isi.',
            'id_target.required' => 'Tahapan harus dipilih.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }

        $cekdatatahapan = Kpitarget::where("id_target", $request->id_target)->first();
        $tahapan = intval(preg_replace('/[^0-9]+/', '', $cekdatatahapan->tahapan));

        $validator->after(function($validator) use ($request, $tahapan) {
            $cekdata = Kpicapaian::where("id_kpi", $request->id_kpi)
                ->where("id_target", $request->id_target)
                ->where("email", Auth::user()->email)
                ->exists();
            if ($cekdata) {
                $validator->errors()->add('id_target', 'Data sudah ada!');
            }

            if (!$request->id_target || $request->id_target == "null") {
                $validator->errors()->add('id_target', 'Target KPI harus dipilih!');
            }

            $tahapansebelumnya = Kpicapaian::where("id_kpi", $request->id_kpi)
                ->where("email", Auth::user()->email)
                ->max("tahapan");

            if ($tahapan != 1 && $tahapansebelumnya + 1 != $tahapan) {
                $validator->errors()->add('id_target', ''.$tahapan.'Tahapan sebelumnya harus di isi!'.$tahapansebelumnya + 1);
            }

            $pjdesa = Pjdesa::where("email", Auth::user()->email)->exists();
            if(!$pjdesa){
                $validator->errors()->add('kendala', 'Akun anda belum di set sebagai kelompok di desa!');
            }

        });

        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $pjdesa = Pjdesa::where("email",Auth::user()->email)->first();
        $data=[
            'id_kpi'=>$request->id_kpi,
            'id_target'=>$request->id_target,
            'email'=>Auth::user()->email,
            'status_capaian'=>$request->status_capaian,
            'tautan'=>$request->tautan,
            'permasalahan'=>$request->permasalahan,
            'solusi'=>$request->solusi,
            'kendala'=>$request->kendala,
            'tahapan'=>$tahapan,
            'id_pjdesa'=>$pjdesa->id_pjdesa,
        ];
        Kpicapaian::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Capaian Key performance indicator berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $kpicapaian = Kpicapaian::where("id_capaian",$request->id_capaian)->first();
        $kpi = Kpi::get();
        $kpitarget = Kpitarget::where("id_kpi",$kpicapaian->id_kpi)->get();
        $data=[
            'kpi'=>$kpi,
            'data'=>$kpicapaian,
            'kpitarget'=>$kpitarget,
        ];
        return view('kpicapaian.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tautan' => 'required',
            'permasalahan' => 'required',
            'solusi' => 'required',
            'kendala' => 'required',
            'id_target' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (!isset($request[$attribute]) || $value === 'null') {
                        $fail('Tahapan harus dipilih.');
                    }
                },
            ],
        ], [
            'tautan.required' => 'Tatutan harus isi.',
            'permasalahan.required' => 'Permasalahan harus isi.',
            'solusi.required' => 'Solusi harus isi.',
            'kendala.required' => 'Kendala harus isi.',
            'id_target.required' => 'Tahapan harus dipilih.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Kpicapaian::where("id_kpi",$request->id_kpi)
            ->where("id_target",$request->id_target)
            ->where("email",Auth::user()->email)
            ->where("id_capaian","!=",$request->id_capaian)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('tautan', 'Data sudah ada!');
            }
            if(!$request->id_target || $request->id_target == "null"){
                $validator->errors()->add('id_target', 'Target KPI harus dipilih!');
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
        $kpicapaian = Kpicapaian::find($request->id_capaian); // Temukan data berdasarkan id
        if (!$kpicapaian) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404); // Menggunakan status kode 404 untuk menunjukkan bahwa data tidak ditemukan
        }
        $cekdatatahapan = Kpitarget::where("id_target",$request->id_target)->first();
        $tahapan = intval(preg_replace('/[^0-9]+/', '', $cekdatatahapan->tahapan));

        $pjdesa = Pjdesa::where("email",Auth::user()->email)->first();
        $data=[
            'id_kpi'=>$request->id_kpi,
            'id_target'=>$request->id_target,
            'email'=>Auth::user()->email,
            'status_capaian'=>$request->status_capaian,
            'tautan'=>$request->tautan,
            'permasalahan'=>$request->permasalahan,
            'solusi'=>$request->solusi,
            'kendala'=>$request->kendala,
            'tahapan'=>$tahapan,
            'id_pjdesa'=>$pjdesa->id_pjdesa,
        ];
        $kpicapaian->update($data);

        //insert data dan tampilkan pesan
        return response()->json([
            'success' => true,
            'message' => 'Capaian key performance indicator berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id_capaian')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_capaian;
            //cek apakah sudah di gunakan di relasi lain
            
            Kpicapaian::where("id_capaian",$id)->where("email",Auth::user()->email)->delete();
    
            // Beri respons berhasil
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }

}