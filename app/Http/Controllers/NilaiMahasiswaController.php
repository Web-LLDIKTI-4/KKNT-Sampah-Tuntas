<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Dpl\NilaiRequest;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Support\ActionButtons;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD bersama untuk nilai mahasiswa yang diinput DPL (konversi & freeform).
 */
abstract class NilaiMahasiswaController extends Controller
{
    use RespondsWithJson;

    /** @return class-string<Model> */
    abstract protected function model(): string;

    abstract protected function viewPrefix(): string;

    abstract protected function routePrefix(): string;

    /** Kolom yang boleh diisi dari form selain nilai */
    abstract protected function fields(): array;

    protected function formData(): array
    {
        return [];
    }

    public function index()
    {
        return view($this->viewPrefix().'.index');
    }

    public function listdata()
    {
        return view($this->viewPrefix().'.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $key = $this->keyName();
        $query = $this->visibleTo($request)->with('mahasiswa.sp')->orderByDesc('created_at');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('nim', fn ($row) => $row->mahasiswa->nim ?? 'NIM Tidak Tersedia')
            ->addColumn('nama', fn ($row) => $row->mahasiswa->nama ?? 'Nama Tidak Tersedia')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? 'Nama Lembaga Tidak Tersedia')
            ->addColumn('prodi', fn ($row) => $row->mahasiswa->prodi ?? 'Prodi Tidak Tersedia')
            ->addColumn('action', fn ($row) => ActionButtons::crud(
                url($this->routePrefix().'/edit/'.$row->{$key}),
                url($this->routePrefix().'/destroy'),
                $key,
                $row->{$key}
            ))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function tambah(Request $request)
    {
        return view($this->viewPrefix().'.tambah', [
            'mahasiswa' => Dplmentoring::ofDpl($request->user())->with('mahasiswa.sp')->get(),
        ] + $this->formData());
    }

    public function edit(Request $request, string $id)
    {
        return view($this->viewPrefix().'.edit', ['data' => $this->ownedOrFail($request, $id)] + $this->formData());
    }

    public function destroy(Request $request)
    {
        $record = $this->owned($request)->find($request->input($this->keyName()));
        if (! $record) {
            return $this->notFound();
        }

        $record->delete();

        return $this->deleted();
    }

    protected function store(NilaiRequest $request)
    {
        $this->model()::create($this->payload($request));

        return $this->saved('Data berhasil disimpan!');
    }

    protected function change(NilaiRequest $request)
    {
        $record = $this->owned($request)->find($request->validated($this->keyName()));
        if (! $record) {
            return $this->notFound();
        }

        $record->update($this->payload($request));

        return $this->saved('Data berhasil disimpan!');
    }

    private function payload(NilaiRequest $request): array
    {
        return $request->safe()->only([...$this->fields(), 'id_mahasiswa', 'nilai_dpl', 'nilai_dpa'])
            + ['email_dpl' => $request->user()->email];
    }

    private function keyName(): string
    {
        return (new ($this->model()))->getKeyName();
    }

    // Record yang diinput DPL yang sedang login
    private function owned(Request $request): Builder
    {
        return $this->model()::where('email_dpl', $request->user()->email);
    }

    private function ownedOrFail(Request $request, string $id): Model
    {
        return $this->owned($request)->findOrFail($id);
    }

    // DPL melihat input miliknya; PT melihat mahasiswa PT-nya; kepala melihat semua
    private function visibleTo(Request $request): Builder
    {
        $user = $request->user();
        if (in_array($user->role, ['pt', 'kepala'], true)) {
            return $this->model()::whereIn('id_mahasiswa', Mahasiswa::visibleTo($user)->select('id_mahasiswa'));
        }

        return $this->owned($request);
    }
}
