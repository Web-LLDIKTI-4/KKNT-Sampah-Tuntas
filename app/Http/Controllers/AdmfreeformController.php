<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\KategoriKegiatan;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Support\XlsxStreamer;
use Yajra\DataTables\EloquentDataTable;

use Illuminate\Support\Facades\Validator;

class AdmfreeformController extends Controller
{    
    use HandlesDataTablesOrder;

    public function index()
    {  
        return view('freeform.index');
    }
    public function listdata()
    {
        return view('freeform.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $this->sanitizeDataTablesInput();

            return $this->table()
                ->addIndexColumn()
                ->addColumn('nim', fn ($row) => $row->mahasiswa?->nim ?? '-')
                ->addColumn('nama', fn ($row) => $row->mahasiswa?->nama ?? '-')
                ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa?->sp?->nm_lemb ?? '-')
                ->addColumn('prodi', fn ($row) => $row->mahasiswa?->prodi ?? '-')
                ->addColumn('nilai_akhir', fn ($row) => self::nilaiAkhir($row))
                ->make(true);
        }
    }
    public function export(Request $request)
    {
        $this->sanitizeDataTablesInput();
        $query = $this->dataTablesExportQuery($this->table(), 'nilai_freeform.id_freeform');

        return (new XlsxStreamer('Konversi Nilai Free Form', [
            XlsxStreamer::column('NIM', fn ($row) => $row->mahasiswa?->nim, 15),
            XlsxStreamer::column('Nama', fn ($row) => $row->mahasiswa?->nama, 30),
            XlsxStreamer::column('Perguruan Tinggi', fn ($row) => $row->mahasiswa?->sp?->nm_lemb, 35),
            XlsxStreamer::column('Prodi', fn ($row) => $row->mahasiswa?->prodi, 25),
            XlsxStreamer::column('Free Form', 'freeform', 40),
            XlsxStreamer::column('Nilai DPL', 'nilai_dpl', 10, 'number'),
            XlsxStreamer::column('Nilai DPA', 'nilai_dpa', 10, 'number'),
            XlsxStreamer::column('Nilai Akhir', fn ($row) => self::nilaiAkhir($row), 11),
        ]))
            ->info(['Kata kunci' => $request->input('search.value')])
            ->download('free_form_'.date('Y-m-d_H-i-s').'.xlsx', $query->lazy(1000));
    }

    // Shared by list & export so filter/search/order are identical
    private function table(): EloquentDataTable
    {
        // View columns only; relation/raw columns from a crafted request are ignored
        $whitelist = ['id_freeform', 'nim', 'nama', 'nm_lemb', 'prodi', 'freeform', 'nilai_dpl', 'nilai_dpa', 'nilai_akhir'];
        $mahasiswa = fn (string $column) => Mahasiswa::select($column)->whereColumn('mahasiswa.id_mahasiswa', 'nilai_freeform.id_mahasiswa');
        $query = Freeform::query()
            ->with(['mahasiswa:id_mahasiswa,nim,nama,prodi,kodept', 'mahasiswa.sp:npsn,nm_lemb'])
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('nilai_freeform.id_freeform'));

        // id_mahasiswa & npsn are unique keys, so the scalar subqueries return at most one row
        return DataTables::eloquent($query)
            ->filterColumn('nim', fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where('nim', 'like', $this->likeContains($k))))
            ->filterColumn('nama', fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where('nama', 'like', $this->likeContains($k))))
            ->filterColumn('prodi', fn ($q, $k) => $q->whereHas('mahasiswa', fn ($m) => $m->where('prodi', 'like', $this->likeContains($k))))
            ->filterColumn('nm_lemb', fn ($q, $k) => $q->whereHas('mahasiswa.sp', fn ($sp) => $sp->where('nm_lemb', 'like', $this->likeContains($k))))
            ->orderColumn('nim', fn ($q, $order) => $q->orderBy($mahasiswa('nim'), $order))
            ->orderColumn('nama', fn ($q, $order) => $q->orderBy($mahasiswa('nama'), $order))
            ->orderColumn('prodi', fn ($q, $order) => $q->orderBy($mahasiswa('prodi'), $order))
            ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy(Satuanpendidikan::select('nm_lemb')->whereIn('npsn', $mahasiswa('kodept')), $order))
            ->orderColumn('nilai_akhir', '(COALESCE(nilai_freeform.nilai_dpl + 0, 0) + COALESCE(nilai_freeform.nilai_dpa + 0, 0)) $1')
            // Computed column: sortable via orderColumn, not searchable
            ->whitelist($whitelist)
            ->blacklist(['nilai_akhir']);
    }

    private static function nilaiAkhir(Freeform $row): float|int
    {
        $nilai_dpl = is_numeric($row->nilai_dpl) ? $row->nilai_dpl : 0;
        $nilai_dpa = is_numeric($row->nilai_dpa) ? $row->nilai_dpa : 0;

        return ($nilai_dpl + $nilai_dpa) / 2;
    }
    
}