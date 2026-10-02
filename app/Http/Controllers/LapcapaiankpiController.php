<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
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
                ->with(['kpi', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
                ->orderByDesc('id_capaian');

            if (in_array(Auth::user()->role, ['dpl'])) {
                $query->whereHas('dplMentoring', function ($q) {
                    $q->where('email_dpl', auth()->user()->email);
                });
            }

            if (Auth::user()->role === 'pt') {
                $query->whereIn('email', \App\Models\Mahasiswa::visibleTo(Auth::user())->select('email'));
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
                    return $row->pjdesa?->mahasiswa?->nama ?? 'Ketua Kelompok Tidak Tersedia';
                })
                ->addColumn('nama_kpi', function($row) {
                    return isset($row->kpi->nama_kpi) ? $row->kpi->nama_kpi : 'Tidak Diketahui';
                })
                ->addColumn('status_capaian', fn ($row) => Kpicapaian::statusBadge($row->status_capaian))
                ->addColumn('tautan', function($row) {
                    return \App\Support\HtmlSanitizer::link($row->tautan) ?: 'Tidak Ada';
                })
                ->rawColumns(['lokasi', 'tautan', 'status_capaian'])
                ->make(true);
        }
        
    }

    public function export(){
        return Excel::download(new CapaiankpiExport, 'capaian_kpi_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}