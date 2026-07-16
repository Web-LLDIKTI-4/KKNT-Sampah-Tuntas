<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Kpitarget;
use App\Models\Kpicapaian;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\CapaiankpiExport;

class LapcapaiankpiController extends Controller
{    
    public function index()
    {  
        return view('lapcapaiankpi.index');
    }
    public function listdata()
    {
        return view('lapcapaiankpi.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $query = Kpicapaian::query();

            if (auth()->user()->role === 'dpl') {
                $query->whereHas('dplMentoring', function ($q) {
                    $q->where('email_dpl', auth()->user()->email);
                });
            }
        
            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('desa', function($row) {
                    return isset($row->pjdesa->desa->desa) ? $row->pjdesa->desa->desa : '-';
                })
                ->addColumn('pjdesa', function($row) {
                    return isset($row->pjdesa->email) ? $row->pjdesa->email : null;
                })
                ->addColumn('nama_kpi', function($row) {
                    return isset($row->kpi->nama_kpi) ? $row->kpi->nama_kpi : null;
                })
                ->addColumn('tahapan', function($row) {
                    return isset($row->target->tahapan) ? $row->target->tahapan : null;
                })
                ->addColumn('nama_kpitarget', function($row) {
                    return isset($row->target->nama_kpitarget) ? $row->target->nama_kpitarget : null;
                })
                ->addColumn('status_capaian', function($row) {
                    if($row->status_capaian == 'Y'){
                        return '<span class="badge bg-success">Sudah Selesai</span>';
                    } elseif ($row->status_capaian == 'P') {
                        return '<span class="badge bg-warning">Proses</span>';
                    } else {
                        return '<span class="badge bg-danger">Belum Ditindaklanjuti</span>';
                    }
                })
                ->addColumn('tautan', function($row) {
                    return $row->tautan ? '<a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>' : null;
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '<div class="d-flex">
                        <a href="#modalku" data-toggle="modal" class="modalButton btn btn-sm p-0 m-0" data-src="'.url('kpicapaian/edit/'.$row->id_capaian).'" title="Edit Data">
                            <i class="fas fa-edit"></i>
                        </a> 
                        <a href="javascript:void(0)" id="hapus_'.$row->id_capaian.'" class="btn btn-sm p-0 m-0">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'status_capaian', 'tautan'])
                ->make(true);
        }
        
    }
    public function export(){
        return Excel::download(new CapaiankpiExport, 'capaian_kpi_'.date('Y-m-d_H-i-s').'.xlsx');
    }

}