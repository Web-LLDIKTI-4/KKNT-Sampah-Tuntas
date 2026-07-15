<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Evaluasikegiatan;
use DB;
use Validator;
use App\Support\ActionButtons;

class AdmevaluasikegiatanController extends Controller
{    
    public function index()
    {  
        return view('evaluasikegiatan.index');
    }
    public function hasilevaluasi()
    {
        return view('evaluasikegiatan.hasilevaluasi');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) { 
            $data = Evaluasikegiatanjawaban::get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nm_lemb', function($row) {
                    if ($row->sp) {
                        return $row->sp->nm_lemb;
                    } else {
                        return 'Tidak ada'; // or any default value you prefer
                    }
                })
                ->addColumn('pertanyaan', function($row) {
                    if ($row->evaluasikegiatan) {
                        return $row->evaluasikegiatan->pertanyaan;
                    } else {
                        return 'Tidak ada'; // or any default value you prefer
                    }
                })
                ->addColumn('action', function($row){
                    return view('components.btn-view', [
                        'url' => url('admlogharian/permhs/'.$row->email.'')
                    ]);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function pertanyaanevaluasi()
    {
        return view('evaluasikegiatan.pertanyaanevaluasi');
    }
    public function pertanyaanevaluasilistdata()
    {
        return view('evaluasikegiatan.pertanyaanevaluasi_listdata');
    }
    public function pertanyaanevaluasiserver(Request $request)
    {
        if ($request->ajax()) { 
            // Menemukan semua mahasiswa dengan kodept yang sesuai
            $data = Evaluasikegiatan::get();           
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('pertanyaan', function($row) {
                return $row->pertanyaan;
            })
            ->addColumn('action', function($row){
                return ActionButtons::editDelete(url('admevaluasikegiatan/pertanyaanevaluasi/destroy'), $row->id_evaluasi);
            })
            ->rawColumns(['pertanyaan', 'action'])
            ->make(true);
        }
    }
    public function tambah()
    {
        return view('evaluasikegiatan.pertanyaanevaluasi_tambah');
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pertanyaan'     => 'required',
        ], [
            'pertanyaan.required' => 'pertanyaan desa harus isi.',
        ]);
        
        $validator->after(function($validator) use ($request) {
            $cekdata = Evaluasikegiatan::where("pertanyaan",$request->pertanyaan)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('pertanyaan', 'Data pertanyaan sudah ada!');
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'pertanyaan'=>$request->pertanyaan,
            'tahun'=>date('Y'),
        ];
        Evaluasikegiatan::create($data);
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function destroy(Request $request){
        $id_evaluasi = $request->id_evaluasi;
        if (Evaluasikegiatan::where("id_evaluasi", $id_evaluasi)->delete()) {
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Data gagal dihapus'], 200);
        }
        
    }
}