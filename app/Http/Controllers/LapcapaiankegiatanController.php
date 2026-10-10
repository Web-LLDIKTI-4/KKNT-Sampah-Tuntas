<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\KategoriKegiatan;
use App\Models\CapaianKegiatan;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\CapaianKegiatanExport;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Utilities\Request as DataTablesRequest;

class LapcapaiankegiatanController extends Controller
{    
    public function index()
    {  
        return view('lapcapaiankegiatan.index');
    }
    public function listdata()
    {
        return view('lapcapaiankegiatan.listdata');
    }
    public function listdataserver(Request $request)
    {
        if ($request->ajax()) {
            $query = CapaianKegiatan::query()
                ->with(['kategoriKegiatan', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
                // Urutan default hanya bila user tidak memilih kolom (agar sort user tidak jadi urutan kedua)
                ->when((new DataTablesRequest)->orderableColumns() === [], fn ($q) => $q
                    ->orderByDesc('capaian_kegiatan.bulan')->orderByDesc('capaian_kegiatan.created_at'));

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
                ->addColumn('nama_kategori', function($row) {
                    return isset($row->kategoriKegiatan->nama_kategori) ? $row->kategoriKegiatan->nama_kategori : 'Tidak Diketahui';
                })
                // Kolom turunan: search/order dipetakan ke relasi agar tidak jadi "Unknown column"
                ->filterColumn('nama_kategori', fn ($q, $keyword) => $q->whereHas('kategoriKegiatan', fn ($k) => $k->where('nama_kategori', 'like', "%{$keyword}%")))
                ->filterColumn('pjdesa', fn ($q, $keyword) => $q->whereHas('pjdesa.mahasiswa', fn ($m) => $m->where('nama', 'like', "%{$keyword}%")))
                ->orderColumn('nama_kategori', '(SELECT nama_kategori FROM kategori_kegiatan WHERE kategori_kegiatan.id_kategori = capaian_kegiatan.id_kategori LIMIT 1) $1')
                ->orderColumn('pjdesa', '(SELECT m.nama FROM pj_desa pj JOIN mahasiswa m ON m.email = pj.email WHERE pj.email = capaian_kegiatan.email LIMIT 1) $1')
                ->addColumn('status_capaian', fn ($row) => CapaianKegiatan::statusBadge($row->status_capaian))
                ->addColumn('tautan', function($row) {
                    return \App\Support\HtmlSanitizer::link($row->tautan) ?: 'Tidak Ada';
                })
                ->rawColumns(['lokasi', 'tautan', 'status_capaian'])
                ->make(true);
        }
        
    }

    public function export(){
        return Excel::download(new CapaianKegiatanExport, 'capaian_kegiatan_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}