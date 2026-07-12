<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Kehadiran;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\LogkehadiranExport;

class LogkehadiranController extends Controller
{    
    public function index()
    {  
        return view('member.kehadiran.index');
    }
    public function listdata()
    {
        return view('member.kehadiran.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Kehadiran::where("email",Auth::user()->email)->get();

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
    public function tambah(){
        $data = Kehadiran::where("email",Auth::user()->email)->where("tanggal",date("Y-m-d"))->first();
        return view('member.kehadiran.tambah',compact('data'));
    }
    public function insert(Request $request)
    {
        $mode = $request->mode;
        $validator = Validator::make($request->all(), [
            //tidak ada
        ], [
            //tidak ada
        ]);
        $validator->after(function($validator) use ($request) {
            $cekdata = Kehadiran::where("email",Auth::user()->email)
                        ->where("tanggal",date("Y-m-d"))
                        ->where("status_kehadiran","!=","hadir")
                        ->exists();
            if ($cekdata) {
                $validator->errors()->add('tanggal', 'Data pada tanggal tersebut terisi!');
            }
        });

        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $cekdata = Kehadiran::where("email", Auth::user()->email)
                            ->where("tanggal", date("Y-m-d"))
                            ->first();

        if($cekdata) {
            //update
            if($mode === "datang") {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_masuk' => date("Y-m-d H:i:s"),
                    'status_kehadiran'=> 'hadir',
                ];
            } else {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_pulang' => date("Y-m-d H:i:s"),
                    'status_kehadiran'=> 'hadir',
                ];
            }
            
            $cekdata->update($data);
            $message = 'Data kehadiran berhasil diperbarui.';
        } else {
            //insert
            if($mode === "datang") {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_masuk' => date("Y-m-d H:i:s"),
                    'status_kehadiran'=> 'hadir',
                ];
            } else {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_pulang' => date("Y-m-d H:i:s"),
                    'status_kehadiran'=> 'hadir',
                ];
            }

            Kehadiran::create($data);
            $message = 'Data kehadiran berhasil ditambahkan.';
        }

        return response()->json(['success'=>true,'message' => $message]);
    }
    public function tambahizin(){
        $status_kehadiran=array("izin","sakit","cuti");
        $data = [
            'status_kehadiran'=>$status_kehadiran
        ];
        return view('member.kehadiran.tambahizin',$data);  
    }
    public function insertizin(Request $request)
    {
        $validator = Validator::make($request->all(), [
           //
        ], [
           //
        ]);
        $validator->after(function($validator) use ($request) {
            $cekdata = Kehadiran::where("email",Auth::user()->email)
                        ->where("tanggal",date("Y-m-d"))
                        ->exists();
            if ($cekdata) {
                $validator->errors()->add('tanggal', 'Data pada tanggal tersebut terisi!');
            }
        });
        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data = [
            'tanggal' => date("Y-m-d"),
            'email' => Auth::user()->email,
            'status_kehadiran'=> $request->status_kehadiran,
            'keterangan' => $request->keterangan,
        ];
        Kehadiran::create($data);
        return response()->json(['success'=>true,'message' => 'laporan berhasil disimpan'], 200);
    }
    public function export(){
        $email = Auth::user()->email;
        return Excel::download(new LogkehadiranExport($email), 'logkehadiran.xlsx');
    }
}