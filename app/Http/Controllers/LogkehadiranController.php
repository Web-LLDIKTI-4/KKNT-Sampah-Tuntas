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
        return view('kehadiran.index');
    }
    public function listdata()
    {
        return view('kehadiran.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Kehadiran::where("email",Auth::user()->email)->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('status_kehadiran', function($row){
                    if ($row->status_kehadiran == 'hadir') {
                        return '<span class="badge bg-success">Hadir</span>';
                    } elseif ($row->status_kehadiran == 'izin') {
                        return '<span class="badge bg-warning">Izin</span>';
                    } elseif ($row->status_kehadiran == 'sakit') {
                        return '<span class="badge bg-danger">Sakit</span>';
                    } elseif ($row->status_kehadiran == 'cuti') {
                        return '<span class="badge bg-info">Cuti</span>';
                    } else {
                        return '<span class="badge bg-secondary">Belum Absen</span>';
                    }
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
                ->rawColumns(['action', 'status_kehadiran', 'coordinates_datang', 'coordinates_pulang'])
                ->make(true);
        }
    }
    public function tambah(){
        $data = Kehadiran::where("email",Auth::user()->email)->where("tanggal",date("Y-m-d"))->first();
        return view('kehadiran.tambah',compact('data'));
    }
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
            
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }

    public function insert(Request $request)
    {
        $mode = $request->mode;
        $validator = Validator::make($request->all(), [
            'mode' => ['required', 'in:datang,pulang'],
            'latitude_datang' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_datang' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude_pulang' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_pulang' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = Kehadiran::where("email",Auth::user()->email)
                        ->where("tanggal",date("Y-m-d"))
                        ->where("status_kehadiran","!=","hadir")
                        ->exists();
            if ($cekdata) {
                $validator->errors()->add('tanggal', 'Data pada tanggal tersebut terisi!');
            }

            // Validasi lokasi/radius sudah dihapus. Koordinat hanya dicatat, tidak divalidasi.
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->with('error', 'Data gagal disimpan!');
        }

        $latitudeDatang  = $request->filled('latitude_datang')  ? (float) $request->latitude_datang  : null;
        $longitudeDatang = $request->filled('longitude_datang') ? (float) $request->longitude_datang : null;
        $latitudePulang  = $request->filled('latitude_pulang')  ? (float) $request->latitude_pulang  : null;
        $longitudePulang = $request->filled('longitude_pulang') ? (float) $request->longitude_pulang : null;

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
                    'latitude_datang' => $latitudeDatang,
                    'longitude_datang' => $longitudeDatang,
                    'status_kehadiran'=> 'hadir',
                ];
            } else {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_pulang' => date("Y-m-d H:i:s"),
                    'latitude_pulang' => $latitudePulang,
                    'longitude_pulang' => $longitudePulang,
                    'status_kehadiran'=> 'hadir',
                ];
            }

            $cekdata->update($data);
            $message = 'Data kehadiran berhasil diupdate, anda melakukan absensi pukul ' . date("H:i:s");
        } else {
            //insert
            if($mode === "datang") {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_masuk' => date("Y-m-d H:i:s"),
                    'latitude_datang' => $latitudeDatang,
                    'longitude_datang' => $longitudeDatang,
                    'status_kehadiran'=> 'hadir',
                ];
            } else {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_pulang' => date("Y-m-d H:i:s"),
                    'latitude_pulang' => $latitudePulang,
                    'longitude_pulang' => $longitudePulang,
                    'status_kehadiran'=> 'hadir',
                ];
            }

            Kehadiran::create($data);
            $message = 'Data kehadiran berhasil ditambahkan, anda melakukan absensi pukul ' . date("H:i:s");
        }

        return redirect()->back()->with('success', $message);
    }

    public function tambahizin(){
        $status_kehadiran=array("izin","sakit","cuti");
        $data = [
            'status_kehadiran'=>$status_kehadiran
        ];
        return view('kehadiran.tambahizin',$data);  
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
        return response()->json(['success'=>true,'message' => 'Laporan izin berhasil disimpan.'], 200);
    }
    public function export(){
        $email = Auth::user()->email;
        return Excel::download(new LogkehadiranExport($email), 'kehadiran_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}