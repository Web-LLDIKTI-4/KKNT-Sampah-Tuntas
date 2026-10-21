<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Pt\PelaksanaanKknRequest;
use App\Models\PelaksanaanKkn;
use App\Services\SebaranPtSummary;

class PelaksanaanKknController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    public function index()
    {
        Gate::authorize('viewAny', PelaksanaanKkn::class);

        return view('pelaksanaankkn.index');
    }

    public function listdata()
    {
        Gate::authorize('viewAny', PelaksanaanKkn::class);

        return view('pelaksanaankkn.listdata');
    }

    public function listdataserver(Request $request, SebaranPtSummary $sebaran)
    {
        abort_unless($request->ajax(), 404);
        Gate::authorize('viewAny', PelaksanaanKkn::class);
        $this->sanitizeDataTablesInput();

        // Subquery agar alias (nama_pt, durasi_hari) bisa dicari/diurutkan tanpa kolom ambigu
        $rows = DB::table('pelaksanaan_kkn')
            ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'pelaksanaan_kkn.kodept')
            ->select([
                'pelaksanaan_kkn.kodept',
                DB::raw('COALESCE(ref_satuanpendidikan.nm_lemb, pelaksanaan_kkn.kodept) as nama_pt'),
                'pelaksanaan_kkn.tanggal_mulai',
                'pelaksanaan_kkn.tanggal_selesai',
                DB::raw('DATEDIFF(pelaksanaan_kkn.tanggal_selesai, pelaksanaan_kkn.tanggal_mulai) + 1 as durasi_hari'),
                'pelaksanaan_kkn.updated_at',
            ]);
        // Kolom lokasi hasil olahan PHP: tidak boleh masuk WHERE/ORDER BY
        $whitelist = ['kodept', 'nama_pt', 'tanggal_mulai', 'tanggal_selesai', 'durasi_hari', 'updated_at'];
        $query = DB::query()->fromSub($rows, 'pelaksanaan')
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('nama_pt')->orderBy('kodept'));
        $lokasiPerPt = $sebaran->lokasiPerPt();

        return DataTables::query($query)
            ->addIndexColumn()
            ->addColumn('lokasi', fn ($row) => implode(', ', $lokasiPerPt[$row->kodept] ?? []) ?: '-')
            ->editColumn('tanggal_mulai', fn ($row) => $this->formatTanggal($row->tanggal_mulai))
            ->editColumn('tanggal_selesai', fn ($row) => $this->formatTanggal($row->tanggal_selesai))
            ->editColumn('durasi_hari', fn ($row) => ((int) $row->durasi_hari).' hari')
            ->editColumn('updated_at', fn ($row) => $row->updated_at ? Carbon::parse($row->updated_at)->translatedFormat('d M Y H:i') : '-')
            ->whitelist($whitelist)
            ->only(['DT_RowIndex', 'kodept', 'nama_pt', 'lokasi', 'tanggal_mulai', 'tanggal_selesai', 'durasi_hari', 'updated_at'])
            ->make(true);
    }

    // Upsert milik PT login; id/kodept dari klien diabaikan
    public function simpan(PelaksanaanKknRequest $request)
    {
        $user = $request->user();
        $attributes = $request->safe()->only(['tanggal_mulai', 'tanggal_selesai']) + [
            'kodept' => $user->email,
            'updated_by' => $user->id,
        ];

        try {
            PelaksanaanKkn::firstOrNew(['kodept' => $user->email])->forceFill($attributes)->save();
        } catch (UniqueConstraintViolationException) {
            // Request paralel sudah membuat record: ulangi sebagai update
            PelaksanaanKkn::where('kodept', $user->email)->firstOrFail()->forceFill($attributes)->save();
        }

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $pelaksanaanKkn = PelaksanaanKkn::where('kodept', $user->email)->first();
        if (! $pelaksanaanKkn) {
            return $this->notFound();
        }
        if (Gate::denies('delete', $pelaksanaanKkn)) {
            return $this->deleteRejected('Anda tidak memiliki akses untuk data ini.', 403);
        }

        $pelaksanaanKkn->delete();

        return $this->deleted();
    }

    private function formatTanggal(?string $tanggal): string
    {
        return $tanggal ? Carbon::parse($tanggal)->translatedFormat('d M Y') : '-';
    }
}
