<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use DataTables;
use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Satuanpendidikan;
use Carbon\Carbon;
use App\Exports\LogbulanandplExport;
use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Models\User;
use App\Support\ActionButtons;


class AdmlaporandplController extends Controller
{
    use HandlesDataTablesOrder;

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
            $this->sanitizeDataTablesInput();

            $whitelist = ['nama_dpl', 'nama_pt', 'count_log'];
            // Builder + eager load: no per-row queries for user, PT, and report count
            $query = Dpl::query()
                ->with(['user:id,email,name', 'sp:npsn,nm_lemb'])
                ->withCount('dpllaporan as count_log')
                ->unless($this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('created_at', 'desc')->orderBy('dpl.id_dpl', 'desc'));

            return Datatables::eloquent($query)
                ->addIndexColumn()
                // count_log is a withCount alias: orderable via orderColumn, not searchable in WHERE
                ->whitelist($whitelist)
                ->blacklist(['count_log'])
                ->addColumn('deskripsi', function($row){
                    return \App\Support\HtmlSanitizer::clean($row->deskripsi).' '.\App\Support\HtmlSanitizer::link($row->tautan);
                })
                ->addColumn('nama_dpl', function($row){
                    return $row->user->name ?? 'Nama DPL Tidak Tersedia';
                })
                ->addColumn('nama_pt', function($row){
                    return $row->sp->nm_lemb ?? 'Nama Perguruan Tinggi Tidak Tersedia';
                })
                ->addColumn('count_log', fn ($row) => $row->count_log)
                ->filterColumn('nama_dpl', fn ($q, $k) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', $this->likeContains($k))))
                ->orderColumn('nama_dpl', fn ($q, $order) => $q->orderBy(
                    User::select('name')->whereColumn('users.email', 'dpl.email')->orderBy('id')->limit(1),
                    $order
                ))
                ->filterColumn('nama_pt', fn ($q, $k) => $q->whereHas('sp', fn ($sp) => $sp->where('nm_lemb', 'like', $this->likeContains($k))))
                ->orderColumn('nama_pt', fn ($q, $order) => $q->orderBy(
                    Satuanpendidikan::select('nm_lemb')->whereColumn('npsn', 'dpl.kodept')->orderBy('id_sp')->limit(1),
                    $order
                ))
                ->orderColumn('count_log', 'count_log $1')
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
            $this->sanitizeDataTablesInput();

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
        return (new LogbulanandplExport($email))->download('logbulanan_dpl_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}