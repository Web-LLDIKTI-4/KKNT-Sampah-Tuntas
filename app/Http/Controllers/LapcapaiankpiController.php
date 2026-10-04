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
use Illuminate\Support\Carbon;
use Yajra\DataTables\Utilities\Request as DataTablesRequest;

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
                // Urutan default hanya bila user tidak memilih kolom (agar sort user tidak jadi urutan kedua)
                ->when((new DataTablesRequest)->orderableColumns() === [], fn ($q) => $q
                    ->orderByDesc('kpi_capaian.bulan')->orderByDesc('kpi_capaian.created_at'));

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
                        return e($row->pjdesa->mahasiswa->user->locationProgram->nama_lokasi).'<br /> '
                            .e($row->pjdesa->desa->kecamatan->kecamatan).', '.e($row->pjdesa->desa->desa);
                    }else {
                        return 'Lokasi Tidak Tersedia';
                    }
                })
                ->addColumn('bulan_label', fn ($row) => $row->bulan ? Carbon::parse($row->bulan)->translatedFormat('F Y') : '-')
                ->addColumn('pjdesa', function($row) {
                    return $row->pjdesa?->mahasiswa?->nama ?? 'Ketua Kelompok Tidak Tersedia';
                })
                ->addColumn('nama_kpi', function($row) {
                    return isset($row->kpi->nama_kpi) ? $row->kpi->nama_kpi : 'Tidak Diketahui';
                })
                // Kolom turunan: search/order dipetakan ke relasi agar tidak jadi "Unknown column"
                ->filterColumn('nama_kpi', fn ($q, $keyword) => $q->whereHas('kpi', fn ($k) => $k->where('nama_kpi', 'like', "%{$keyword}%")))
                ->filterColumn('pjdesa', fn ($q, $keyword) => $q->whereHas('pjdesa.mahasiswa', fn ($m) => $m->where('nama', 'like', "%{$keyword}%")))
                ->orderColumn('nama_kpi', '(SELECT nama_kpi FROM kpi WHERE kpi.id_kpi = kpi_capaian.id_kpi LIMIT 1) $1')
                ->orderColumn('pjdesa', '(SELECT m.nama FROM pj_desa pj JOIN mahasiswa m ON m.email = pj.email WHERE pj.email = kpi_capaian.email LIMIT 1) $1')
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