<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\MentoringAssignRequest;
use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Services\MentoringAssignmentService;
use App\Support\ActionButtons;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

// Admin: penugasan mahasiswa ke DPL (route group memakai DplmentoringPolicy::manage)
class AdmmentoringController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    public function index()
    {
        return view('admmentoring.index');
    }

    public function listdata()
    {
        return view('admmentoring.listdata', [
            'dpls' => $this->selectableDpls(),
            'pts' => Satuanpendidikan::whereIn('npsn', Dpl::select('kodept'))->orderBy('nm_lemb')->get(['npsn', 'nm_lemb']),
        ]);
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $emailDpl = $this->filterValue($request->query('email_dpl'));
        $kodept = $this->filterValue($request->query('kodept'));
        $whitelist = ['nim', 'nama', 'nm_lemb', 'prodi', 'nama_dpl', 'email_dpl', 'created_at'];
        $query = Dplmentoring::query()
            ->with(['mahasiswa.sp', 'dpl'])
            ->when($emailDpl !== null, fn ($q) => $q->where('email_dpl', $emailDpl))
            ->when($kodept !== null, fn ($q) => $q->whereIn('email_mahasiswa', Mahasiswa::select('email')->where('kodept', $kodept)))
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->latest('dpl_mentoring.created_at'));

        $dataTable = DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->addColumn('nim', fn ($row) => $row->mahasiswa->nim ?? 'tidak ada')
            ->addColumn('nama', fn ($row) => $row->mahasiswa->nama ?? 'tidak ada')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? 'tidak terdata')
            ->addColumn('prodi', fn ($row) => $row->mahasiswa->prodi ?? 'tidak ada')
            ->addColumn('nama_dpl', fn ($row) => $row->dpl->nama ?? 'tidak terdata')
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('d-m-Y H:i'))
            ->addColumn('action', fn ($row) => ActionButtons::make(
                urlDelete: route('admmentoring.destroy'),
                idField: 'id_mentoring',
                idValue: $row->id_mentoring,
                confirm: 'Batalkan penugasan mahasiswa ini dari DPL?',
            ))
            ->rawColumns(['action'])
            ->only(['DT_RowIndex', 'id_mentoring', 'nim', 'nama', 'nm_lemb', 'prodi', 'nama_dpl', 'email_dpl', 'created_at', 'action'])
            ->filterColumn('nm_lemb', fn ($q, $k) => $this->whereRelatedLike($q, 'mahasiswa.sp', 'nm_lemb', $k))
            ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy($this->menteeValue(
                Satuanpendidikan::select('nm_lemb')->whereColumn('npsn', 'mahasiswa.kodept')->orderBy('npsn')->limit(1)
            ), $order))
            ->filterColumn('nama_dpl', fn ($q, $k) => $this->whereRelatedLike($q, 'dpl', 'nama', $k))
            ->orderColumn('nama_dpl', fn ($q, $order) => $q->orderBy(
                Dpl::select('dpl.nama')->whereColumn('dpl.email', 'dpl_mentoring.email_dpl')->orderBy('dpl.id_dpl')->limit(1),
                $order
            ));
        // Relation-derived columns: search via IN subquery, sort via correlated subquery
        foreach (['nim' => 'mahasiswa.nim', 'nama' => 'mahasiswa.nama', 'prodi' => 'mahasiswa.prodi'] as $alias => $column) {
            $dataTable
                ->filterColumn($alias, fn ($q, $k) => $this->whereRelatedLike($q, 'mahasiswa', $column, $k))
                ->orderColumn($alias, fn ($q, $order) => $q->orderBy($this->menteeValue($column), $order));
        }

        return $this->makeWithTieBreaker($dataTable, 'dpl_mentoring.id_mentoring');
    }

    public function tambah()
    {
        return view('admmentoring.tambah', ['dpls' => $this->selectableDpls()]);
    }

    public function kandidat(string $id_dpl, MentoringAssignmentService $service)
    {
        $dpl = Dpl::with('sp')->findOrFail($id_dpl);
        $kodeptMissing = blank($dpl->kodept) || blank($dpl->email);

        return view('admmentoring.kandidat', [
            'dpl' => $dpl,
            'data' => $kodeptMissing ? new Collection : $service->candidates($dpl->kodept)->with(['sp', 'locationProgram'])->get(),
            'kodeptMissing' => $kodeptMissing,
        ]);
    }

    public function insert(MentoringAssignRequest $request, MentoringAssignmentService $service)
    {
        $dpl = Dpl::find($request->validated('id_dpl'));
        if (blank($dpl?->kodept) || blank($dpl?->email)) {
            return response()->json(['error' => 'DPL belum memiliki perguruan tinggi di Data DPL']);
        }

        $inserted = $service->assign($dpl->email, $dpl->kodept, $request->validated('createuser'));

        return response()->json(['success' => $inserted.' mahasiswa berhasil ditugaskan']);
    }

    public function destroy(Request $request)
    {
        // Array/non-uuid input rejected so find() never returns a Collection
        $id = $request->input('id_mentoring');
        $mentoring = is_string($id) && Str::isUuid($id) ? Dplmentoring::find($id) : null;
        if (! $mentoring) {
            return $this->notFound();
        }

        $mentoring->delete();

        return $this->deleted('Penugasan berhasil dibatalkan');
    }

    // DPL without email/PT cannot be assigned
    private function selectableDpls(): Collection
    {
        return Dpl::with('sp')
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereNotNull('kodept')->where('kodept', '!=', '')
            ->orderBy('nama')
            ->get(['id_dpl', 'nama', 'email', 'kodept']);
    }

    private function filterValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    // Single deterministic mahasiswa value per mentoring row (email is not unique in mahasiswa)
    private function menteeValue(string|QueryBuilder|EloquentBuilder $column): EloquentBuilder
    {
        return Mahasiswa::query()
            ->select(['value' => $column])
            ->whereColumn('mahasiswa.email', 'dpl_mentoring.email_mahasiswa')
            ->orderByDesc('mahasiswa.id_mahasiswa')
            ->limit(1);
    }
}
