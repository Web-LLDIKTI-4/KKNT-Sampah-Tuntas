<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Logbulanan;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\LogBulananByMhsExport;

class AdmlogbulananController extends Controller
{    
    public function index()
    {  
        return view('logbulanan.dpl.index');
    }
    public function listdatagroup()
    {
        return view('logbulanan.dpl.listdatagroup');
    }
    public function listdatagrouping(Request $request)
    {
        if ($request->ajax()) {
            $query = Mahasiswa::query()
                ->with(['dplmentoring'])
                ->orderBy('created_at', 'desc');

            if (in_array(Auth::user()->role, ['dpl'])) {
                $query->whereHas('dplmentoring', function ($query) {
                    $query->where('email_dpl', Auth::user()->email);
                })->get();
            }
            
            if (in_array(Auth::user()->role, ['pt'])) {
                // Query validasi by lokasi program dan kode pt
                $emailMahasiswa = User::query()
                    ->with(['mahasiswa'])
                    ->where('location_program', Auth::user()->location_program)
                    ->whereHas('mahasiswa', function ($q) {
                        $q->where('kodept', Auth::user()->pt->npsn);
                    })
                    ->where('role', 'mahasiswa')
                    ->pluck('email');
                $query->whereIn('email', $emailMahasiswa)->get();
            }

            return Datatables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('nim', function($row){
                    return $row->nim ?? 'NIM tidak tersedia';
                })
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->nama ?? 'Nama tidak tersedia';
                })
                ->addColumn('email', function($row){
                    return $row->email ?? 'Email tidak tersedia';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->sp->nm_lemb ?? 'Nama Perguruan Tinggi tidak tersedia';
                })
                ->addColumn('count_log', function($row){
                    return $row->logbulanan->count() ?? '0';
                })
                ->addColumn('action', function($row){
                    return view('components.action-data', [
                        'urlView' => url('admlogbulanan/listdata/'.$row->email)
                    ]);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function listdata()
    {
        return view('logbulanan.dpl.listdata');
    }
    public function listdataserver(Request $request, String $email)
    {

        if ($request->ajax()) {
            $data = Logbulanan::where('email', $email)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->mahasiswa->nama ?? 'Nama tidak tersedia';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? 'Nama Perguruan Tinggi tidak tersedia';
                })
                ->addColumn('deskripsi', function($row){
                    return $row->deskripsi.'<br><a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                })
                ->addColumn('nama_bulan', function($row){
                    return Carbon::create()->month($row->bulan)->translatedFormat('F');
                })
                ->addColumn('action', function($row){
                    if (Auth::check() && Auth::user()->role == 'dpl' && $row->nilai == null) {
                        return view('components.btn-modal', [
                            'url' => url('admlogbulanan/formpenilaian/'.$row->id_logbulanan),
                            'title' => 'Penilaian Log Bulanan',
                            'slot' => 'Berikan Nilai',
                        ])->render();
                    } else {
                        return $row->nilai;
                    }
                })
                ->rawColumns(['action'])
                ->make(true);
        }
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
        $logbulanan = Logbulanan::find($request->id_logbulanan);
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

    public function export(String $email){
        return Excel::download(new LogBulananByMhsExport($email), 'logbulanan_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}