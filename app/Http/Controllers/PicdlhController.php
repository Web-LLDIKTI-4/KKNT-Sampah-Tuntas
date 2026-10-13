<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Master\PicdlhRequest;
use App\Models\Kecamatan;
use App\Models\Picdlh;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PicdlhController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    private const KETERANGAN_ORDER = "CASE pic_dlh.keterangan WHEN 'ketua' THEN 1 WHEN 'anggota' THEN 2 WHEN 'lldikti' THEN 3 ELSE 4 END";

    public function index()
    {
        return view('picdlh.index', ['canManage' => $this->canManage()]);
    }

    public function listdata()
    {
        return view('picdlh.listdata', ['canManage' => $this->canManage()]);
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $canManage = $this->canManage();
        $whitelist = ['kecamatan', 'nama', 'kontak', 'keterangan'];

        // Cap page size at 100; yajra skips paging when length is -1 or start/length is missing
        $length = (int) $request->input('length', 10);
        $request->merge([
            'start' => max(0, (int) $request->input('start', 0)),
            'length' => $length >= 1 && $length <= 100 ? $length : 100,
        ]);

        $query = Picdlh::query()
            ->leftJoin('kecamatan', 'kecamatan.id_kecamatan', '=', 'pic_dlh.id_kecamatan')
            ->select('pic_dlh.id_pic_dlh', 'kecamatan.kecamatan as kecamatan', 'pic_dlh.nama', 'pic_dlh.kontak', 'pic_dlh.keterangan')
            // Default order only when the client sends none (yajra appends user order after base order)
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q
                ->orderBy('kecamatan.kecamatan')
                ->orderByRaw(self::KETERANGAN_ORDER)
                ->orderBy('pic_dlh.nama')
                ->orderBy('pic_dlh.id_pic_dlh'),
                // Deterministic tie-breaker; beforeQuery runs at SQL compile, so it lands after yajra's user order
                fn ($q) => $q->beforeQuery(fn ($base) => $base
                    ->orderBy('pic_dlh.nama')
                    ->orderBy('pic_dlh.id_pic_dlh')));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->editColumn('kecamatan', fn (Picdlh $row) => $row->kecamatan ?? '')
            ->editColumn('keterangan', fn (Picdlh $row) => Picdlh::KETERANGAN[$row->keterangan] ?? $row->keterangan)
            ->filterColumn('kecamatan', fn ($query, $keyword) => $query->where('kecamatan.kecamatan', 'like', "%{$keyword}%"))
            ->filterColumn('nama', fn ($query, $keyword) => $query->where('pic_dlh.nama', 'like', "%{$keyword}%"))
            ->filterColumn('kontak', fn ($query, $keyword) => $query->where('pic_dlh.kontak', 'like', "%{$keyword}%"))
            ->filterColumn('keterangan', fn ($query, $keyword) => $query->where('pic_dlh.keterangan', 'like', "%{$keyword}%"))
            ->orderColumn('kecamatan', 'kecamatan.kecamatan $1')
            ->orderColumn('nama', 'pic_dlh.nama $1')
            ->orderColumn('kontak', 'pic_dlh.kontak $1')
            ->orderColumn('keterangan', self::KETERANGAN_ORDER.' $1')
            ->addColumn('action', fn (Picdlh $row) => $canManage ? ActionButtons::make(
                urlEdit: url('picdlh/edit/'.$row->id_pic_dlh),
                urlDelete: url('picdlh/destroy'),
                idField: 'id_pic_dlh',
                idValue: $row->id_pic_dlh,
            ) : '')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah()
    {
        abort_unless($this->canManage(), 403);

        return view('picdlh.tambah', [
            'kecamatan' => Kecamatan::orderBy('kecamatan')->get(),
            'keterangan' => Picdlh::KETERANGAN,
        ]);
    }

    public function insert(PicdlhRequest $request)
    {
        abort_unless($this->canManage(), 403);

        Picdlh::create($request->safe()->only('id_kecamatan', 'nama', 'kontak', 'keterangan'));

        return $this->saved();
    }

    public function edit(string $id_pic_dlh)
    {
        abort_unless($this->canManage(), 403);
        abort_unless(Str::isUuid($id_pic_dlh), 404);

        return view('picdlh.edit', [
            'data' => Picdlh::findOrFail($id_pic_dlh),
            'kecamatan' => Kecamatan::orderBy('kecamatan')->get(),
            'keterangan' => Picdlh::KETERANGAN,
        ]);
    }

    public function update(PicdlhRequest $request)
    {
        abort_unless($this->canManage(), 403);

        Picdlh::findOrFail($request->validated('id_pic_dlh'))
            ->update($request->safe()->only('id_kecamatan', 'nama', 'kontak', 'keterangan'));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        abort_unless($this->canManage(), 403);

        $id = $request->input('id_pic_dlh');
        // Non-string/non-uuid id would make find() return a Collection
        $pic = Str::isUuid($id) ? Picdlh::find($id) : null;
        if (! $pic) {
            return $this->notFound();
        }

        $pic->delete();

        return $this->deleted();
    }

    // Hanya admin mengelola PIC DLH; role lain hanya melihat
    private function canManage(): bool
    {
        return auth()->user()->role === 'admin';
    }
}
