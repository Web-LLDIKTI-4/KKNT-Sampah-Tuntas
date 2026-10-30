<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

// Tugas akhir table shared by admin & dpl/pt/kepala/pemda lists and their exports
trait BuildsTugasakhirTable
{
    use HandlesDataTablesOrder;

    // Call after sanitizeDataTablesInput(); role scope lives here so list and export match
    protected function tugasakhirTable(User $user): EloquentDataTable
    {
        $whitelist = ['id_tugasakhir', 'nim', 'nama', 'nm_lemb', 'tautan', 'nilai_dpl'];

        // dplmentoring unused; eager load sp to avoid N+1
        $query = Tugasakhir::query()
            ->without('dplmentoring')
            ->with(['mahasiswa:id_mahasiswa,email,nim,nama,kodept', 'mahasiswa.sp:npsn,nm_lemb'])
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->latest());

        // dpl: mentored students; pt: own PT students; admin/kepala/pemda: all
        match ($user->role) {
            'dpl' => $query->whereHas('dplmentoring', fn ($q) => $q->where('email_dpl', $user->email)),
            'admin', 'kepala', 'pemda' => $query,
            default => $query->whereHas('mahasiswa', fn ($q) => $q->visibleTo($user)),
        };

        // Map relation-derived columns so search & sort don't error
        return DataTables::eloquent($query)
            ->filterColumn('nim', fn ($q, $k) => $this->whereRelatedLike($q, 'mahasiswa', 'nim', $k))
            ->filterColumn('nama', fn ($q, $k) => $this->whereRelatedLike($q, 'mahasiswa', 'nama', $k))
            ->filterColumn('nm_lemb', fn ($q, $k) => $this->whereRelatedLike($q, 'mahasiswa.sp', 'nm_lemb', $k))
            ->orderColumn('nim', fn ($q, $order) => $q->orderBy($this->mahasiswaSubquery('nim'), $order))
            ->orderColumn('nama', fn ($q, $order) => $q->orderBy($this->mahasiswaSubquery('nama'), $order))
            ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy(
                Satuanpendidikan::select('nm_lemb')
                    ->whereIn('npsn', Mahasiswa::select('kodept')->whereColumn('mahasiswa.email', 'tugasakhir.email'))
                    ->orderBy('npsn')
                    ->limit(1),
                $order
            ))
            ->whitelist($whitelist);
    }

    protected function tugasakhirExport(Request $request, string $filename): StreamedResponse
    {
        $this->sanitizeDataTablesInput();
        $query = $this->dataTablesExportQuery($this->tugasakhirTable($request->user()), 'tugasakhir.id_tugasakhir');

        return (new XlsxStreamer('Laporan Tugas Akhir', [
            XlsxStreamer::column('NIM', 'mahasiswa.nim', 15),
            XlsxStreamer::column('Nama', 'mahasiswa.nama', 30),
            XlsxStreamer::column('Perguruan Tinggi', 'mahasiswa.sp.nm_lemb', 35),
            XlsxStreamer::column('Tautan', 'tautan', 45),
            XlsxStreamer::column('Nilai', 'nilai_dpl', 8, 'number'),
        ]))
            ->info(['Kata kunci' => $request->input('search.value')])
            ->download($filename, $query->lazy(1000));
    }

    // Deterministic single-row lookup of a mahasiswa column for ordering
    private function mahasiswaSubquery(string $column)
    {
        return Mahasiswa::select($column)
            ->whereColumn('mahasiswa.email', 'tugasakhir.email')
            ->orderByDesc('mahasiswa.id_mahasiswa')
            ->limit(1);
    }
}
