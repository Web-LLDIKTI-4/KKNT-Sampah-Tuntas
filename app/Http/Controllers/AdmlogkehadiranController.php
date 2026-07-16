<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Kehadiran;
use App\Models\Dplmentoring;
use App\Exports\LogKehadiranByMhsExport;

use Maatwebsite\Excel\Facades\Excel;

class AdmlogkehadiranController extends Controller
{    
    public function index()
    {  
        return view('logkehadiran.index');
    }
    public function listdatagroup()
    {
        return view('logkehadiran.listdatagroup');
    }
    
    public function listdatagrouping(Request $request)
    {
        if ($request->ajax()) {
            if (Auth::check() && Auth::user()->role == 'dpl') {
                $data = Mahasiswa::with(['dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })->get();
            } else {
                $data = Mahasiswa::with(['dplmentoring'])->get();
            }
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row){
                    return $row->nim ?? 'NIM tidak tersedia';
                })
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->nama ?? 'Nama tidak tersedia';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->sp->nm_lemb ?? 'Perguruan Tinggi tidak tersedia';
                })
                ->addColumn('count_log', function($row){
                    return $row->logkehadiran->count() ?? '0';
                })
                ->addColumn('action', function($row){
                    return view('components.action-data', [
                        'urlView' => url('admlogkehadiran/listdata/' . $row->email ?? ''),
                    ]);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function listdata()
    {
        return view('logkehadiran.listdata');
    }
    
    public function listdataserver(Request $request, String $email)
    {
        if ($request->ajax()) {
            $data = Kehadiran::where('email', $email)->get();
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row){
                    return $row->mahasiswa->nim ?? 'NIM tidak tersedia';
                })
                ->addColumn('nama_mahasiswa', function($row){
                    return $row->mahasiswa->nama ?? 'Nama tidak tersedia';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? 'Perguruan Tinggi tidak tersedia';
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
                ->rawColumns(['coordinates_datang', 'coordinates_pulang'])
                ->make(true);
        }
    }

    public function export(String $email){
        return Excel::download(new LogKehadiranByMhsExport($email), 'kehadiran_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}