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
                    return 'No PT'; // or any default value you prefer
                }
            })
            ->addColumn('pertanyaan', function($row) {
                if ($row->evaluasikegiatan) {
                    return $row->evaluasikegiatan->pertanyaan;
                } else {
                    return 'No PT'; // or any default value you prefer
                }
            })
            ->addColumn('action', function($row){
                $actionBtn = '<div class="d-felx"><a href="'.url('admlogharian/permhs/'.$row->email.'').'" class="btn btn-sm p-0 m-0">lihat data</a> </div>';
                return $actionBtn;
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
            ->addColumn('action', function($row){
                $actionBtn = '<div class="d-felx"><a href="'.url('#').'" class="p-0 m-0"><i class="ri-delete-bin-line"></i></a> </div>';
                return $actionBtn;
            })
            ->rawColumns(['action'])
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
    
}