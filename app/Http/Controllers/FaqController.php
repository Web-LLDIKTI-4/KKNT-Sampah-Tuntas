<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use App\Support\ActionButtons;

class FaqController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    private const FIELDS = ['pertanyaan', 'jawaban'];

    public function index()
    {
        return view('faq.index');
    }

    public function listdata()
    {
        return view('faq.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $whitelist = ['pertanyaan', 'jawaban'];
        $query = Faq::query()
            ->select(['id', 'pertanyaan', 'jawaban'])
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('id'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->whitelist($whitelist)
            ->editColumn('pertanyaan', fn (Faq $row) => '<div class="text-wrap">'.e($row->pertanyaan).'</div>')
            ->editColumn('jawaban', fn (Faq $row) => '<div class="text-wrap">'.nl2br(e($row->jawaban)).'</div>')
            ->addColumn('action', fn (Faq $row) => ActionButtons::make(
                urlEdit: url('faq/edit/'.$row->id),
                urlDelete: url('faq/destroy'),
                idField: 'id',
                idValue: $row->id,
            ))
            ->rawColumns(['pertanyaan', 'jawaban', 'action'])
            ->only(['DT_RowIndex', 'pertanyaan', 'jawaban', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('faq.tambah');
    }

    public function insert(FaqRequest $request)
    {
        Faq::create($request->safe()->only(self::FIELDS));

        return $this->saved();
    }

    public function edit(int $id)
    {
        return view('faq.edit', ['faq' => Faq::findOrFail($id)]);
    }

    public function update(FaqRequest $request)
    {
        Faq::findOrFail($request->validated('id'))->update($request->safe()->only(self::FIELDS));

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $faq = $this->findFaqByInput($request->input('id'));
        if (! $faq) {
            return $this->notFound();
        }

        Gate::authorize('delete', $faq);
        $faq->delete();

        return $this->deleted();
    }

    // Input array/non-digit ditolak agar find() tidak mengembalikan Collection
    private function findFaqByInput(mixed $faqId): ?Faq
    {
        if (! is_scalar($faqId) || ! ctype_digit((string) $faqId)) {
            return null;
        }

        return Faq::find((int) $faqId);
    }
}
