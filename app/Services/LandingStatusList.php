<?php

namespace App\Services;

use App\Support\LikeKeyword;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Sudah/belum lists behind the landing status badges; populations match DataPesertaSummary::status() & SebaranPtSummary::statusLokasi()
class LandingStatusList
{
    // Sortable response fields per jenis (validated in StatusListRequest)
    public const SORT_FIELDS = [
        'mobilisasi' => ['nim', 'nama', 'nama_pt', 'kecamatan', 'desa'],
        'pelaksanaan' => ['kode_pt', 'nama_pt'],
        'lokasi' => ['nim', 'nama', 'nama_pt', 'lokasi', 'kecamatan', 'desa'],
    ];

    public function __construct(
        private DataPesertaSummary $peserta,
        private SebaranPtSummary $sebaran,
    ) {}

    public function list(string $jenis, string $status, ?string $q, int $page, int $perPage, ?string $sort = null, string $dir = 'asc'): LengthAwarePaginator
    {
        $sudah = $status === 'sudah';
        $like = trim((string) $q) === '' ? null : LikeKeyword::contains(trim((string) $q));

        $query = match ($jenis) {
            'mobilisasi' => $this->mobilisasi($sudah, $like, $sort, $dir),
            'pelaksanaan' => $this->pelaksanaan($sudah, $like, $sort, $dir),
            'lokasi' => $this->lokasi($sudah, $like, $sort, $dir),
        };

        // Out-of-range page: count only, skip the OFFSET query
        $total = $query->getCountForPagination();
        $items = ($page - 1) * $perPage < $total ? $query->forPage($page, $perPage)->get() : collect();
        if ($jenis === 'lokasi') {
            $items = $this->lokasiRows($items->pluck('id_mahasiswa'), $sudah);
        }

        return (new LengthAwarePaginator($items, $total, $perPage, $page))
            ->through(fn ($row) => match ($jenis) {
                'mobilisasi' => [
                    'nim' => $row->nim,
                    'nama' => $row->nama,
                    'nama_pt' => $row->nm_lemb ?? $row->kodept,
                    'kecamatan' => $row->kecamatan,
                    'desa' => $row->desa,
                ],
                'pelaksanaan' => [
                    'kode_pt' => (string) $row->kodept,
                    'nama_pt' => (string) ($row->nm_lemb ?? $row->kodept),
                ],
                'lokasi' => [
                    'nim' => $row->nim,
                    'nama' => $row->nama,
                    'nama_pt' => (string) ($row->nm_lemb ?? $row->kodept),
                    'lokasi' => $row->nama_lokasi,
                    'kecamatan' => $row->kecamatan,
                    'desa' => $row->desa,
                ],
            });
    }

    // Whitelisted SQL only; NULLs last in both directions, then a unique key for stable paging
    private function sorted(Builder $query, array $columns, ?string $sort, string $dir, string $default, string $key): Builder
    {
        $column = $columns[$sort] ?? $columns[$default];
        $bindings = [];
        if ($column instanceof Builder) {
            $bindings = $column->getBindings();
            $column = '('.$column->toSql().')';
        }
        $dir = $dir === 'desc' ? 'desc' : 'asc';

        return $query->orderByRaw($column.' IS NULL', $bindings)->orderByRaw($column.' '.$dir, $bindings)->orderBy($key);
    }

    private function mobilisasi(bool $sudah, ?string $like, ?string $sort, string $dir): Builder
    {
        // One mahasiswa (MAX id, index-only lookup on mahasiswa.email) per pj_desa row; avoids a derived table joined without a key
        $query = $this->peserta->mobilisasiBase()
            ->leftJoin('mahasiswa as m', 'm.id_mahasiswa', '=', DB::raw('(select max(m2.id_mahasiswa) from mahasiswa m2 where m2.email = pj_desa.email)'))
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->leftJoin('desa as d', 'd.id_desa', '=', 'pj_desa.id_desa')
            ->leftJoin('kecamatan as k', 'k.id_kecamatan', '=', 'd.id_kecamatan')
            ->when(
                $sudah,
                fn ($b) => $b->where('pj_desa.status_mobilisasi', 'sudah'),
                fn ($b) => $b->where(fn ($w) => $w->whereNull('pj_desa.status_mobilisasi')->orWhere('pj_desa.status_mobilisasi', '<>', 'sudah')),
            )
            ->when($like, fn ($b) => $b->where(fn ($w) => $w->where('m.nama', 'like', $like)->orWhere('m.nim', 'like', $like)->orWhere('sp.nm_lemb', 'like', $like)))
            ->select('m.nim', 'm.nama', 'm.kodept', 'sp.nm_lemb', 'k.kecamatan', 'd.desa');

        return $this->sorted($query, [
            'nim' => 'm.nim',
            'nama' => 'm.nama',
            'nama_pt' => 'COALESCE(sp.nm_lemb, m.kodept)',
            'kecamatan' => 'k.kecamatan',
            'desa' => 'd.desa',
        ], $sort, $dir, 'nama', 'pj_desa.id_pjdesa');
    }

    private function pelaksanaan(bool $sudah, ?string $like, ?string $sort, string $dir): Builder
    {
        $exists = fn ($q) => $q->from('pelaksanaan_kkn')->whereColumn('pelaksanaan_kkn.kodept', 'p.kodept');

        $query = DB::query()->fromSub($this->peserta->kodeptBase(), 'p')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'p.kodept')
            ->when($sudah, fn ($b) => $b->whereExists($exists), fn ($b) => $b->whereNotExists($exists))
            ->when($like, fn ($b) => $b->where(fn ($w) => $w->where('sp.nm_lemb', 'like', $like)->orWhere('p.kodept', 'like', $like)))
            ->select('p.kodept', 'sp.nm_lemb');

        return $this->sorted($query, [
            'kode_pt' => 'p.kodept',
            'nama_pt' => 'COALESCE(sp.nm_lemb, p.kodept)',
        ], $sort, $dir, 'nama_pt', 'p.kodept');
    }

    // Deferred join: lokasi() pages narrow id rows, display columns are fetched for that page only
    private function lokasi(bool $sudah, ?string $like, ?string $sort, string $dir): Builder
    {
        if ($sudah) {
            // Latest desa per mahasiswa (MAX tahun, then MAX id_desa); inner join = has a desa, same population as SUDAH_LOKASI_SQL
            $tahunTerakhir = DB::table('mahasiswa_lokasi')->whereNotNull('id_desa')
                ->groupBy('id_mahasiswa')->selectRaw('id_mahasiswa, MAX(tahun) as tahun');
            $desaTerakhir = DB::table('mahasiswa_lokasi as mlx')
                ->joinSub($tahunTerakhir, 'lt', fn ($j) => $j->on('lt.id_mahasiswa', '=', 'mlx.id_mahasiswa')->on('lt.tahun', '=', 'mlx.tahun'))
                ->whereNotNull('mlx.id_desa')
                ->groupBy('mlx.id_mahasiswa')
                ->selectRaw('mlx.id_mahasiswa, MAX(mlx.id_desa) as id_desa');

            // Derived table drives the join so mahasiswa/desa/kecamatan are PK lookups
            $base = DB::query()->fromSub($desaTerakhir, 'dt')
                ->join('mahasiswa as m', 'm.id_mahasiswa', '=', 'dt.id_mahasiswa')
                ->whereNotNull('m.location_program')->whereNotNull('m.kodept')
                ->leftJoin('desa as d', 'd.id_desa', '=', 'dt.id_desa')
                ->leftJoin('kecamatan as k', 'k.id_kecamatan', '=', 'd.id_kecamatan');
            [$kecamatan, $desa] = ['k.kecamatan', 'd.desa'];
        } else {
            $base = $this->sebaran->lokasiBase()->whereRaw('NOT '.SebaranPtSummary::SUDAH_LOKASI_SQL);
            [$kecamatan, $desa] = ['NULL', 'NULL'];
        }

        $base->leftJoin('lokasi_program as lp', 'lp.id', '=', 'm.location_program')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->when($like, fn ($b) => $b->where(fn ($w) => $w->where('m.nama', 'like', $like)->orWhere('m.nim', 'like', $like)->orWhere('sp.nm_lemb', 'like', $like)))
            ->select('m.id_mahasiswa');

        return $this->sorted($base, [
            'nim' => 'm.nim',
            'nama' => 'm.nama',
            'nama_pt' => 'COALESCE(sp.nm_lemb, m.kodept)',
            'lokasi' => 'lp.nama_lokasi',
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ], $sort, $dir, 'nama', 'm.id_mahasiswa');
    }

    private function lokasiRows(Collection $ids, bool $sudah): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        // Latest desa (MAX tahun, then MAX id_desa), same as the derived table in lokasi(); runs for page rows only
        $desaTerakhir = fn (string $kolom) => DB::table('mahasiswa_lokasi as mlx')
            ->leftJoin('desa as d', 'd.id_desa', '=', 'mlx.id_desa')
            ->leftJoin('kecamatan as k', 'k.id_kecamatan', '=', 'd.id_kecamatan')
            ->whereColumn('mlx.id_mahasiswa', 'm.id_mahasiswa')->whereNotNull('mlx.id_desa')
            ->orderByDesc('mlx.tahun')->orderByDesc('mlx.id_desa')->limit(1)
            ->select($kolom);

        $rows = DB::table('mahasiswa as m')->whereIn('m.id_mahasiswa', $ids)
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'm.location_program')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->select('m.id_mahasiswa', 'm.nim', 'm.nama', 'm.kodept', 'sp.nm_lemb', 'lp.nama_lokasi')
            ->when(
                $sudah,
                fn ($b) => $b->selectSub($desaTerakhir('k.kecamatan'), 'kecamatan')->selectSub($desaTerakhir('d.desa'), 'desa'),
                fn ($b) => $b->addSelect(DB::raw('NULL as kecamatan'), DB::raw('NULL as desa')),
            )
            ->get()->keyBy('id_mahasiswa');

        // Skip ids deleted between the page query and this one; keeps page order
        return $ids->filter(fn ($id) => $rows->has($id))->map(fn ($id) => $rows[$id])->values();
    }
}
