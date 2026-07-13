<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Logbulanan;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\LogbulananmhsExport;

class AdmlogbulananController extends Controller
{    
    public function index()
    {  
        return view('logbulanan.dpl.index');
    }
    public function listdata()
    {
        return view('logbulanan.dpl.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check() && Auth::user()->role == 'dpl') {
                $data = Logbulanan::with(['mahasiswa', 'dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })
                    ->get();
            } else {
                $data = Logbulanan::with(['mahasiswa'])->get();
            }           

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->mahasiswa->nama;
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb;
                })
                ->addColumn('deskripsi', function($row){
                    return $row->deskripsi.'<br><a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                })
                ->addColumn('nama_bulan', function($row){
                    return Carbon::create()->month($row->bulan)->format('F');
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx">';
                    if (Auth::check() && Auth::user()->role == 'dpl') {
                        $actionBtn.= '<a href="#modalku" data-bs-toggle="modal" data-src="'.url('admlogbulanan/formpenilaian/'.$row->id_logbulanan.'').'" class="modalButton p-0 m-0" title="Penilaian">berikan <br>penilaian</a>';
                    }
                    $actionBtn.= '<hr> <h4>'.$row->nilai.'</h4>';
                    $actionBtn.= '</div>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function export(){
        return Excel::download(new LogbulananmhsExport, 'logbulanan_mahasiswa.xlsx');
    }
    public function formpenilaian(Request $request){
        $logbulanan=Logbulanan::find($request->id);
        $anilai = array('10','20','30','40','50','60','70','80','90','100');
        $data=[
            'logbulanan'=>$logbulanan,
            'anilai'=>$anilai,
        ];
        return view('logbulanan.dpl.penilaian',$data);
    }
    public function updatenilai(Request $request){

        $logbulanan=Logbulanan::find($request->id_logbulanan);
        if ($logbulanan) {
            $logbulanan->update([
                'nilai' => $request->nilai,
                'hasil_verifikasi' => $request->hasil_verifikasi,
                'verifikator' => Auth::user()->email,
            ]);
            return response()->json(['success'=>true,'message' => 'Data berhasil diupdate'], 200);
        } else {
            return response()->json(['success'=>false,'message' => 'Data tidak ditemukan'], 404);
        }        
    }
}