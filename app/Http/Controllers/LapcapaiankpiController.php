<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\User;
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
            $query = Kpicapaian::query()
                ->orderByDesc('id_capaian');

            if (in_array(Auth::user()->role, ['dpl'])) {
                $query->whereHas('dplMentoring', function ($q) {
                    $q->where('email_dpl', auth()->user()->email);
                });
            }

            if (in_array(Auth::user()->role, ['pt'])) {
                // Query validasi by lokasi program dan kode pt
                $emailMahasiswa = User::query()
                    ->with(['mahasiswa'])
                    ->where('location_program', Auth::user()->location_program)
                    ->whereHas('mahasiswa', function ($q) {
                        $q->where('kodept', Auth::user()->pt->npsn);
                    })
                    ->where('role', 'mahasiswa')
                    ->pluck('email');
                $query->whereIn('email', $emailMahasiswa)->get();
            }
            
            return Datatables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('lokasi', function($row) {
                    if (isset($row->pjdesa->mahasiswa->user->locationProgram->nama_lokasi ) && isset($row->pjdesa->desa->kecamatan->kecamatan) && isset($row->pjdesa->desa->desa)) {
                        return $row?->pjdesa?->mahasiswa?->user?->locationProgram?->nama_lokasi . '<br /> ' . $row?->pjdesa?->desa?->kecamatan?->kecamatan . ', ' . $row?->pjdesa?->desa?->desa;
                    }else {
                        return 'Lokasi Tidak Tersedia';
                    }
                })
                ->addColumn('pjdesa', function($row) {
                    return isset($row->pjdesa->email) ? $row->pjdesa->email : 'Ketua Kelompok Tidak Tersedia';
                })
                ->addColumn('nama_kpi', function($row) {
                    return isset($row->kpi->nama_kpi) ? $row->kpi->nama_kpi : 'Tidak Diketahui';
                })
                ->addColumn('tahapan', function($row) {
                    return isset($row->target->tahapan) ? $row->target->tahapan : 'Tidak Diketahui';
                })
                ->addColumn('nama_kpitarget', function($row) {
                    return isset($row->target->nama_kpitarget) ? $row->target->nama_kpitarget : 'Tidak Diketahui';
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
                    return $row->tautan ? '<a href="'.$row->tautan.'" target="_blank">'.$row->tautan.'</a>' : 'Tidak Ada';
                })
                // ->addColumn('action', function($row) {
                //     $actionBtn = '<div class="d-flex">
                //         <a href="#modalku" data-toggle="modal" class="modalButton btn btn-sm p-0 m-0" data-src="'.url('kpicapaian/edit/'.$row->id_capaian).'" title="Edit Data">
                //             <i class="fas fa-edit"></i>
                //         </a> 
                //         <a href="javascript:void(0)" id="hapus_'.$row->id_capaian.'" class="btn btn-sm p-0 m-0">
                //             <i class="fa fa-trash"></i>
                //         </a>
                //     </div>';
                //     return $actionBtn;
                // })
                ->rawColumns(['lokasi', 'action', 'tautan', 'status_capaian'])
                ->make(true);
        }
        
    }

    public function export(){
        return Excel::download(new CapaiankpiExport, 'capaian_kpi_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}