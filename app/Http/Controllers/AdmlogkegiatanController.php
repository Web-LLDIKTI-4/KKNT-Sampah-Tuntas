<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Logkegiatan;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use App\Exports\LogHarianByMhsExport;

class AdmlogkegiatanController extends Controller
{    
    public function index()
    {  
        return view('logkegiatan.dpl.index');
    }

    public function listdatagroup() {
        return view('logkegiatan.dpl.listdatagroup');
    }

    public function listdatagrouping(Request $request) {
        if ($request->ajax()) {
            if (Auth::check() && Auth::user()->role == 'dpl') {
                $data = Mahasiswa::with(['dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })
                    ->get();
            } else {
                $data = Mahasiswa::all();
            }           

            return Datatables::of($data)
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
                    return $row->logkegiatan->count() ?? '0';
                })
                ->addColumn('action', function($row){
                    return view('components.action-data', [
                        'urlView' => url('admlogkegiatan/listdata/'.$row->email),
                    ]);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function listdata()
    {
        return view('logkegiatan.dpl.listdata');
    }

    public function listdataserver(Request $request, String $email)
    {

        if ($request->ajax()) {
            $data = Logkegiatan::where('email', $email)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function($row){
                    return \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y');
                })
                ->addColumn('nama_kpi', function($row){
                    return $row->kpi->nama_kpi;
                })
                ->addColumn('deskripsi', function($row){
                    
                    if (Auth::check() &&  in_array(Auth::user()->role, ['admin', 'dpl'])) {
                        return $row->deskripsi.'<a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                    }else{
                        return 'tidak ditampilkan <a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>';
                    }
                })
                ->make(true);
        }
    }

    public function export(String $email){
        return Excel::download(new LogHarianByMhsExport($email), 'logharian_mahasiswa_'.$email.'_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}