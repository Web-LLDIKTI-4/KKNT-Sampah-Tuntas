<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Dpl;
use App\Models\Dpllaporan;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\LogbulanandplExport;
use App\Support\ActionButtons;
use App\Support\DataTableOrder;
use App\Models\User;
use App\Models\Satuanpendidikan;


class AdmlaporandplController extends Controller
{    
    public function index()
    {  
        return view('laporandpl.index');
    }
    public function listdatagroup()
    {
        return view('laporandpl.listdatagroup');
    }
    public function listdatagrouping(Request $request)
    {
        if ($request->ajax()) {
            
            // Jumlah laporan via withCount (bukan 1 COUNT per baris); relasi di-eager load
            $query = Dpl::query()
                ->select('dpl.id_dpl', 'dpl.email', 'dpl.kodept')
                ->with(['user:email,name', 'sp:npsn,nm_lemb'])
                ->withCount('dpllaporan')
                ->when(! DataTableOrder::requested(), fn ($q) => $q->orderByDesc('dpl.created_at'));

            return Datatables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('deskripsi', function($row){
                    return \App\Support\HtmlSanitizer::clean($row->deskripsi).' '.\App\Support\HtmlSanitizer::link($row->tautan);
                })
                ->addColumn('nama_dpl', function($row){
                    return $row->user->name ?? 'Nama DPL Tidak Tersedia';
                })
                ->addColumn('nama_pt', function($row){
                    return $row->sp->nm_lemb ?? 'Nama Perguruan Tinggi Tidak Tersedia';
                })
                ->addColumn('count_log', fn ($row) => $row->dpllaporan_count)
                ->filterColumn('nama_dpl', fn ($q, $keyword) => $q->whereIn('dpl.email', User::select('email')->where('name', 'like', "%{$keyword}%")))
                ->filterColumn('nama_pt', fn ($q, $keyword) => $q->whereIn('dpl.kodept', Satuanpendidikan::where('nm_lemb', 'like', "%{$keyword}%")->pluck('npsn')->all()))
                ->orderColumn('nama_dpl', '(SELECT name FROM users WHERE users.email = dpl.email LIMIT 1) $1')
                ->orderColumn('nama_pt', '(SELECT nm_lemb FROM ref_satuanpendidikan WHERE npsn = dpl.kodept LIMIT 1) $1')
                ->orderColumn('count_log', 'dpllaporan_count $1')
                ->addColumn('action', function($row){
                    return ActionButtons::make(urlView: url('admlaporandpl/listdata/'.rawurlencode($row->email)));
                })
                ->rawColumns(['action', 'deskripsi'])
                ->make(true);
        }
    }

    public function listdata()
    {
        return view('laporandpl.listdata');
    }
    public function listdataserver(Request $request, String $email)
    {

        if ($request->ajax()) {
            
            $query = Dpllaporan::where('email', $email)->orderBy('created_at', 'desc')->get();
            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('nama_bulan', function($row){
                    return Carbon::create()->month($row->bulan)->translatedFormat('F');
                })
                ->addColumn('tahun', function($row){
                    return $row->tahun;
                })
                ->addColumn('deskripsi', function($row){
                    return \App\Support\HtmlSanitizer::clean($row->deskripsi).' '.\App\Support\HtmlSanitizer::link($row->tautan);
                })
                ->rawColumns(['deskripsi'])
                ->make(true);
        }
    }
    public function export(String $email){
        return Excel::download(new LogbulanandplExport($email), 'logbulanan_dpl_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}