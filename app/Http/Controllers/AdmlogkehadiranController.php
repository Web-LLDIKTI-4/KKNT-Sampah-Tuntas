<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Kehadiran;
use App\Models\Dplmentoring;
use App\Exports\LogkehadiranExport;

use Maatwebsite\Excel\Facades\Excel;

class AdmlogkehadiranController extends Controller
{    
    public function index()
    {  
        return view('logkehadiran.index');
    }
    public function listdata()
    {
        return view('logkehadiran.listdata');
    }
    
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check() && Auth::user()->role == 'dpl') {
                $data = Kehadiran::with(['mahasiswa', 'dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })
                    ->get();
            } else {
                $data = Kehadiran::with(['mahasiswa'])->get();
            }
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->mahasiswa->nama ?? '-';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? '-';
                })
                ->addColumn('tanggal', function($row){
                    return $row->tanggal ? date('d-m-Y', strtotime($row->tanggal)) : '-';
                })
                ->addColumn('waktu_masuk', function($row){
                    return $row->waktu_masuk ? date('H:i:s', strtotime($row->waktu_masuk)) . ' WIB' : '-';
                })
                ->addColumn('coordinates_datang', function($row){
                    return '<a href="https://www.google.com/maps?q=' . $row->latitude_datang . ',' . $row->longitude_datang . '" target="_blank" class="btn btn-sm btn-primary">Lihat Map</a>';
                    // return view('components.embed-map', [
                    //     'latitude' => $row->latitude_datang,
                    //     'longitude' => $row->longitude_datang,
                    // ]);
                })
                ->addColumn('waktu_pulang', function($row){
                    return $row->waktu_pulang ? date('H:i:s', strtotime($row->waktu_pulang)) . ' WIB' : '-';
                })
                ->addColumn('coordinates_pulang', function($row){
                    return '<a href="https://www.google.com/maps?q=' . $row->latitude_pulang . ',' . $row->longitude_pulang . '" target="_blank" class="btn btn-sm btn-primary">Lihat Map</a>';
                    // return view('components.embed-map', [
                    //     'latitude' => $row->latitude_pulang,
                    //     'longitude' => $row->longitude_pulang,
                    // ]);
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-pencil-square"></i></a> <a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-trash"></i></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'coordinates_datang', 'coordinates_pulang'])
                ->make(true);
        }
    }

    public function export(){
        $email = Auth::user()->email;
        return Excel::download(new LogkehadiranExport($email), 'kehadiran_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}