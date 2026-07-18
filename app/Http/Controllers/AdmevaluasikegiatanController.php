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
use App\Models\Satuanpendidikan;

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
                ->addColumn('kodept', function($row) {
                    return $row->kodept ?? 'Tidak ada';
                })
                ->addColumn('nm_lemb', function($row) {
                    return Satuanpendidikan::where('npsn', $row->kodept)->first()->nm_lemb ?? 'Tidak ada';
                })
                ->addColumn('pertanyaan', function($row) {
                    return $row->evaluasikegiatan->pertanyaan ?? 'Tidak ada';
                })
                ->addColumn('action', function($row){
                    return view('components.btn-view', [
                        'url' => url('admlogharian/permhs/'.$row->email.'')
                    ]);
                })
                ->rawColumns(['pertanyaan', 'action'])
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
                return $row->pertanyaan ?? 'Tidak ada';
            })
            ->addColumn('action', function($row){
                return view('components.action-data', [
                    'urlEdit' => url('admevaluasikegiatan/edit/'.$row->id_evaluasi.''),
                    'urlDelete' => $row->id_evaluasi
                ]);
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
    public function edit($id_evaluasi)
    {
        $data = Evaluasikegiatan::find($id_evaluasi);
        return view('evaluasikegiatan.pertanyaanevaluasi_edit', compact('data'));
    }
    public function update(Request $request)
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

        $update = Evaluasikegiatan::findOrFail($request->id_evaluasi);
        $update->update($data);
        
        //insert data dan tampilkan pesan
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }

    public function destroy(Request $request){
        $id_evaluasi = $request->id_evaluasi;
        if (Evaluasikegiatan::where("id_evaluasi", $id_evaluasi)->delete()) {
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Data gagal dihapus, data tersebut tidak ada atau sudah terhapus!'], 200);
        }
        
    }
}