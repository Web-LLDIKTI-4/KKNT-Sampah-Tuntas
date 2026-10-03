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
use Illuminate\Support\Collection;
use App\Exports\LogkegiatanExport;
use DB;
use App\Support\ActionButtons;
class AdmlogharianController extends Controller
{    
    public function index()
    {  
        return view('logharian.index');
    }
    public function listdata()
    {
        return view('logharian.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) { 

            // jumlah_log via subquery (index email,tanggal); tidak bisa dicari/diurutkan karena berat
            $query = Mahasiswa::query()
                ->select('mahasiswa.kodept', 'mahasiswa.nim', 'mahasiswa.nama', 'mahasiswa.email', 'ref_satuanpendidikan.nm_lemb')
                ->selectSub(Logkegiatan::selectRaw('count(distinct tanggal)')->whereColumn('logkegiatan.email', 'mahasiswa.email'), 'jumlah_log')
                ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'mahasiswa.kodept');

            return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? 'Perguruan Tinggi tidak ditemukan')
            ->filterColumn('nm_lemb', fn ($q, $keyword) => $q->where('ref_satuanpendidikan.nm_lemb', 'like', "%{$keyword}%"))
            ->blacklist(['jumlah_log'])
            ->addColumn('action', function($row){
                return ActionButtons::make(urlView: url('admlogharian/permhs/'.rawurlencode($row->email)));
            })
            ->rawColumns(['action'])
            ->make(true);
        }
    }
    public function permhs(Request $request)
    {
        $email = $request->email;
        return view('logharian.permhs',compact('email'));
    }
    public function permhsserver(Request $request)
    {
        if ($request->ajax()) { 
            // Menemukan semua mahasiswa dengan kodept yang sesuai
            return DataTables::eloquent(Logkegiatan::where('email', $request->email))
            ->addIndexColumn()
            ->make(true);
        }
    }
    public function export(){
        return Excel::download(new LogkegiatanExport, 'logharian_mahasiswa.xlsx');
    }
}