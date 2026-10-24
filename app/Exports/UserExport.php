<?php

namespace App\Exports;

use App\Support\LikeKeyword;
use App\Support\XlsxStreamer;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExport
{
    public function __construct(private array $filters = []) {}

    // Filter sama dengan User::scopeManaged(), diproses per chunk agar hemat memori
    public function query(): Builder
    {
        $kodept = $this->filters['kodept'] ?? null;

        return DB::table('users as u')
            // MAX(id) per email: cegah baris ganda bila email master tidak unik
            ->leftJoin('mahasiswa as m', fn (JoinClause $j) => $j->on('m.email', '=', 'u.email')
                ->where('u.role', 'mahasiswa')
                ->whereRaw('m.id_mahasiswa = (select max(m2.id_mahasiswa) from mahasiswa m2 where m2.email = u.email)'))
            ->leftJoin('dpl as d', fn (JoinClause $j) => $j->on('d.email', '=', 'u.email')
                ->where('u.role', 'dpl')
                ->whereRaw('d.id_dpl = (select max(d2.id_dpl) from dpl d2 where d2.email = u.email)'))
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', DB::raw("(case u.role when 'mahasiswa' then m.kodept when 'dpl' then d.kodept else u.email end)"))
            ->whereIn('u.role', ['mahasiswa', 'dpl', 'pt', 'kepala', 'pemda'])
            ->when($this->filters['role'] ?? null, fn ($q, $role) => $q->where('u.role', $role))
            ->when($kodept, fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('u.role', 'mahasiswa')->whereIn('u.email', DB::table('mahasiswa')->select('email')->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('u.role', 'dpl')->whereIn('u.email', DB::table('dpl')->select('email')->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('u.role', 'pt')->where('u.email', $kodept))))
            ->when($this->filters['q'] ?? null, fn ($q, $keyword) => $this->applySearch($q, $keyword))
            ->select([
                'u.id',
                'u.name',
                'u.email',
                'u.role',
                DB::raw("(case u.role when 'mahasiswa' then m.nim when 'dpl' then d.nidn else sp.npsn end) as nomor_induk"),
                'sp.nm_lemb',
            ]);
    }

    // Mirrors the table's smart search; keyword is pre-capped by HandlesDataTablesOrder::limitDataTablesSearch()
    private function applySearch(Builder $query, string $keyword): void
    {
        $terms = preg_split('/ +/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY);
        $idNumber = "(case u.role when 'mahasiswa' then m.nim when 'dpl' then d.nidn end)";
        $ptName = "(case u.role when 'mahasiswa' then coalesce(nullif(sp.nm_lemb, ''), m.kodept)
            when 'dpl' then coalesce(nullif(sp.nm_lemb, ''), d.kodept) when 'pt' then sp.nm_lemb end)";

        foreach ($terms as $term) {
            $like = LikeKeyword::contains($term);
            $query->where(fn ($q) => $q->where('u.email', 'like', $like)
                ->orWhere('u.name', 'like', $like)
                ->orWhere('u.role', 'like', $like)
                ->orWhereRaw("{$idNumber} like ?", [$like])
                ->orWhereRaw("{$ptName} like ?", [$like]));
        }
    }

    // Streams XLSX row by row so memory stays constant
    public function writeTo(string $path): void
    {
        (new XlsxStreamer('Data Pengguna', $this->columns()))
            ->info($this->filterInfo())
            ->writeTo($path, $this->query()->lazyById(1000, 'u.id', 'id'));
    }

    // Selected rows only, in the given order (ids capped by BulkSelectionRequest)
    public function downloadSelected(array $ids, string $filename): StreamedResponse
    {
        $position = array_flip($ids);
        $rows = $this->query()->whereIn('u.id', $ids)->get()->sortBy(fn ($row) => $position[$row->id])->values();

        return (new XlsxStreamer('Data Pengguna', $this->columns()))
            ->info(['Data' => $rows->count().' baris terpilih'])
            ->download($filename, $rows);
    }

    private function columns(): array
    {
        return [
            XlsxStreamer::column('Nama', 'name', 30),
            XlsxStreamer::column('NIM/NIDN/Kode PT', 'nomor_induk', 20),
            XlsxStreamer::column('Email', 'email', 32),
            XlsxStreamer::column('Perguruan Tinggi', 'nm_lemb', 40),
            XlsxStreamer::column('Peran', fn ($row) => ucfirst($row->role), 12),
        ];
    }

    private function filterInfo(): array
    {
        $kodept = $this->filters['kodept'] ?? null;

        return [
            'Peran' => ucfirst($this->filters['role'] ?? ''),
            'Perguruan Tinggi' => $kodept ? (DB::table('ref_satuanpendidikan')->where('npsn', $kodept)->value('nm_lemb') ?? $kodept) : null,
            'Kata kunci' => $this->filters['q'] ?? null,
        ];
    }
}
