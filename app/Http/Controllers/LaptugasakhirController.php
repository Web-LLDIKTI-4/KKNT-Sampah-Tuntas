<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\KategoriKegiatan;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\LaptugasakhirExport;
use App\Http\Controllers\Concerns\HandlesDataTablesOrder;

class LaptugasakhirController extends Controller
{    
    use HandlesDataTablesOrder;

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
            $this->sanitizeDataTablesInput();
            $whitelist = ['id_tugasakhir', 'nim', 'nama', 'nm_lemb', 'tautan', 'nilai_dpl'];

            // dplmentoring tidak dipakai; sp di-eager load agar tidak N+1
            $query = Tugasakhir::query()
                ->without('dplmentoring')
                ->with(['mahasiswa:id_mahasiswa,email,nim,nama,kodept', 'mahasiswa.sp:npsn,nm_lemb'])
                ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->latest());

            return Datatables::of($query)
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
                // Kolom turunan relasi dipetakan agar search & sort tidak error
                ->filterColumn('nim', fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where('nim', 'like', "%{$k}%")))
                ->filterColumn('nama', fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where('nama', 'like', "%{$k}%")))
                ->filterColumn('nm_lemb', fn ($q, $k) => $q->whereHas('mahasiswa.sp', fn ($sp) => $sp->where('nm_lemb', 'like', "%{$k}%")))
                ->orderColumn('nim', fn ($q, $order) => $q->orderBy(Mahasiswa::select('nim')->whereColumn('mahasiswa.email', 'tugasakhir.email')->limit(1), $order))
                ->orderColumn('nama', fn ($q, $order) => $q->orderBy(Mahasiswa::select('nama')->whereColumn('mahasiswa.email', 'tugasakhir.email')->limit(1), $order))
                ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy(
                    Satuanpendidikan::select('nm_lemb')->whereIn('npsn', Mahasiswa::select('kodept')->whereColumn('mahasiswa.email', 'tugasakhir.email'))->limit(1),
                    $order
                ))
                ->rawColumns(['tautan'])
                ->whitelist($whitelist)
                ->make(true);
        }
        
    }
    public function export(){
        return Excel::download(new LaptugasakhirExport, 'tugas_akhir_'.date('d-m-Y_H-i-s').'.xlsx');
    }

}