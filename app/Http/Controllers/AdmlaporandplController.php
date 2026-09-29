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
            
            $query = Dpl::query()
                ->orderBy('created_at', 'desc')->get();

            return Datatables::of($query)
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
                ->addColumn('count_log', function($row){
                    return $row->dpllaporan()->count() ?? '0';
                })
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
                // ->addColumn('action', function($row){
                //     $actionBtn = '<div class="d-felx"><a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-pencil-square"></i></a> <a href="javascript:void(0)" class="btn btn-sm p-0 m-0"><i class="bi bi-trash"></i></a></div>';
                //     return $actionBtn;
                // })
                ->rawColumns(['action', 'deskripsi'])
                ->make(true);
        }
    }
    public function export(String $email){
        return Excel::download(new LogbulanandplExport($email), 'logbulanan_dpl_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}