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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required' => 'Lokasi belum terdeteksi. Pastikan GPS aktif dan izinkan akses lokasi.',
            'longitude.required' => 'Lokasi belum terdeteksi. Pastikan GPS aktif dan izinkan akses lokasi.',
        ]);
        $validator->after(function($validator) use ($request) {
            $cekdata = Kehadiran::where("email",Auth::user()->email)
                        ->where("tanggal",date("Y-m-d"))
                        ->where("status_kehadiran","!=","hadir")
                        ->exists();
            if ($cekdata) {
                $validator->errors()->add('tanggal', 'Data pada tanggal tersebut terisi!');
            }

            // Validasi jarak Backend
            $latitude = (float) $request->latitude;
            $longitude = (float) $request->longitude;
            $radiusMax = config('attendance.radius_meter', 100);
            $officeLocations = config('attendance.locations', []);
            
            $isWithinRadius = false;
            foreach ($officeLocations as $office) {
                $distance = $this->calculateDistance($latitude, $longitude, $office['latitude'], $office['longitude']);
                if ($distance <= $radiusMax) {
                    $isWithinRadius = true;
                    break;
                }
            }

            if (!$isWithinRadius) {
                $validator->errors()->add('latitude', 'Anda berada di luar radius lokasi absen yang diizinkan (maksimal 100 meter).');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->with('error', 'Data gagal disimpan!');
        }

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;

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
                    'latitude_datang' => $latitude,
                    'longitude_datang' => $longitude,
                    'status_kehadiran'=> 'hadir',
                ];
            } else {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_pulang' => date("Y-m-d H:i:s"),
                    'latitude_pulang' => $latitude,
                    'longitude_pulang' => $longitude,
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
                    'latitude_datang' => $latitude,
                    'longitude_datang' => $longitude,
                    'status_kehadiran'=> 'hadir',
                ];
            } else {
                $data = [
                    'tanggal' => date("Y-m-d"),
                    'email' => Auth::user()->email,
                    'waktu_pulang' => date("Y-m-d H:i:s"),
                    'latitude_pulang' => $latitude,
                    'longitude_pulang' => $longitude,
                    'status_kehadiran'=> 'hadir',
                ];
            }

            Kehadiran::create($data);
            $message = 'Data kehadiran berhasil ditambahkan.';
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
        return Excel::download(new LogkehadiranExport($email), 'logkehadiran.xlsx');
    }
}