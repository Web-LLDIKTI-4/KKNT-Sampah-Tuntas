<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Tugasakhir;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\LaptugasakhirExport;

class LaptugasakhirController extends Controller
{    
    public function index()
    {  
        return view('laptugasakhir.index');
    }
    public function listdata()
    {
        return view('laptugasakhir.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            // Route khusus admin; mahasiswa lewat email (tidak unik) → eager load + filter subquery
            $query = Tugasakhir::query()->with(['mahasiswa:email,nim,nama,kodept', 'mahasiswa.sp:npsn,nm_lemb']);

            return Datatables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('nim', function($row) {
                    return $row->mahasiswa->nim ?? '-';
                })
                ->addColumn('nama', function($row) {
                    return $row->mahasiswa->nama ?? '-';
                })
                ->addColumn('nm_lemb', function($row) {
                    return $row->mahasiswa->sp->nm_lemb ?? '-';
                })
                ->addColumn('tautan', function($row) {
                    return \App\Support\HtmlSanitizer::link($row->tautan) ?: null;
                })
                ->filterColumn('nim', fn ($q, $keyword) => $q->whereIn('tugasakhir.email', Mahasiswa::select('email')->where('nim', 'like', "%{$keyword}%")))
                ->filterColumn('nama', fn ($q, $keyword) => $q->whereIn('tugasakhir.email', Mahasiswa::select('email')->where('nama', 'like', "%{$keyword}%")))
                ->filterColumn('nm_lemb', fn ($q, $keyword) => $q->whereIn('tugasakhir.email', Mahasiswa::select('email')
                    ->whereIn('kodept', Satuanpendidikan::where('nm_lemb', 'like', "%{$keyword}%")->pluck('npsn')->all())))
                ->orderColumn('nim', '(SELECT nim FROM mahasiswa WHERE mahasiswa.email = tugasakhir.email LIMIT 1) $1')
                ->orderColumn('nama', '(SELECT nama FROM mahasiswa WHERE mahasiswa.email = tugasakhir.email LIMIT 1) $1')
                ->orderColumn('nm_lemb', '(SELECT sp.nm_lemb FROM mahasiswa m JOIN ref_satuanpendidikan sp ON sp.npsn = m.kodept WHERE m.email = tugasakhir.email LIMIT 1) $1')
                ->rawColumns(['tautan'])
                ->make(true);
        }
        
    }
    public function export(){
        return Excel::download(new LaptugasakhirExport, 'capaian_kpi.xlsx');
    }

}