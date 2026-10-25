<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Models\Mahasiswa;
use App\Support\XlsxStreamer;
use Illuminate\Http\Request;
use Session;
use App\Models\CapaianKegiatan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

class LapcapaiankegiatanController extends Controller
{
    use HandlesDataTablesOrder;

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
            $this->sanitizeDataTablesInput();

            return $this->table($request)
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
                ->addColumn('status_capaian', fn ($row) => CapaianKegiatan::statusBadge($row->status_capaian))
                ->addColumn('tautan', function($row) {
                    return \App\Support\HtmlSanitizer::link($row->tautan) ?: 'Tidak Ada';
                })
                ->rawColumns(['lokasi', 'tautan', 'status_capaian'])
                ->make(true);
        }

    }

    // Also used by CapaianKegiatanController::export for non-mahasiswa roles
    public function export(Request $request)
    {
        $this->sanitizeDataTablesInput();
        $query = $this->dataTablesExportQuery($this->table($request), 'capaian_kegiatan.id_capaian');

        return (new XlsxStreamer('Laporan Evaluasi Pendataan Sampah', [
            XlsxStreamer::column('Bulan', fn ($row) => $row->bulan ? Carbon::parse($row->bulan)->translatedFormat('F Y') : null, 14),
            XlsxStreamer::column('Lokasi Kegiatan', fn ($row) => implode(', ', array_filter([
                $row->pjdesa?->mahasiswa?->user?->locationProgram?->nama_lokasi,
                $row->pjdesa?->desa?->kecamatan?->kecamatan,
                $row->pjdesa?->desa?->desa,
            ])), 30),
            XlsxStreamer::column('PJ Desa', 'email', 28),
            XlsxStreamer::column('Desa', 'pjdesa.desa.desa', 20),
            XlsxStreamer::column('Permasalahan', 'permasalahan', 40),
            XlsxStreamer::column('Solusi', 'solusi', 40),
            XlsxStreamer::column('Kebutuhan Dukungan', 'kendala', 40),
            XlsxStreamer::column('Tindak Lanjut', fn ($row) => CapaianKegiatan::STATUS[$row->status_capaian] ?? CapaianKegiatan::STATUS['N'], 14),
            XlsxStreamer::column('Tautan', 'tautan', 30),
        ]))
            ->info(['Kata kunci' => $request->input('search.value')])
            ->download('evaluasi_pendataan_sampah_'.date('Y-m-d_H-i-s').'.xlsx', $query->lazy(1000));
    }

    // Call after sanitizeDataTablesInput(); scope, search & order shared by list and export
    private function table(Request $request): EloquentDataTable
    {
        $user = $request->user();
        // Only columns shown in the view; blocks dotted relation names like user.password
        $whitelist = ['bulan', 'pjdesa', 'permasalahan', 'solusi', 'kendala', 'status_capaian', 'tautan'];
        $query = CapaianKegiatan::query()
            ->with(['pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram'])
            // Urutan default hanya bila user tidak memilih kolom (agar sort user tidak jadi urutan kedua)
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q
                ->orderByDesc('capaian_kegiatan.bulan')->orderByDesc('capaian_kegiatan.created_at'));

        if ($user->role === 'dpl') {
            $query->whereHas('dplMentoring', fn ($q) => $q->where('email_dpl', $user->email));
        }

        if ($user->role === 'pt') {
            $query->whereIn('email', Mahasiswa::visibleTo($user)->select('email'));
        }

        // Kolom turunan: search/order dipetakan ke relasi agar tidak jadi "Unknown column"
        return DataTables::eloquent($query)
            ->filterColumn('pjdesa', fn ($q, $keyword) => $q->whereHas('pjdesa.mahasiswa', fn ($m) => $m->where('nama', 'like', $this->likeContains($keyword))))
            ->orderColumn('pjdesa', '(SELECT m.nama FROM pj_desa pj JOIN mahasiswa m ON m.email = pj.email WHERE pj.email = capaian_kegiatan.email ORDER BY m.id_mahasiswa DESC LIMIT 1) $1')
            ->whitelist($whitelist);
    }
}
