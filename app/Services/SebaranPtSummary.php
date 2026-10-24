<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SebaranPtSummary
{
    // Mahasiswa (alias m) already set desa; shared by statusLokasi() and the landing list
    public const SUDAH_LOKASI_SQL = 'EXISTS (SELECT 1 FROM mahasiswa_lokasi ml WHERE ml.id_mahasiswa = m.id_mahasiswa AND ml.id_desa IS NOT NULL)';

    // PT (alias pt) has a ketua kelompok: same join as jumlah_ketua_kelompok in DataPesertaSummary::all(); shared by statusKetua() and the landing list
    public const SUDAH_KETUA_SQL = 'EXISTS (SELECT 1 FROM mahasiswa km JOIN pj_desa pd ON pd.email = km.email WHERE km.kodept = pt.kodept)';

    // Ikut versi peta: flush data mahasiswa/dpl ikut menyegarkan cache ini
    public function cached(): Collection
    {
        return Cache::remember('login.sebaran_pt.v2.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1), now()->addMinutes(10), fn () => $this->all());
    }

    /**
     * PT–lokasi program–kecamatan–desa aktual (desa dari lokasi terakhir mahasiswa, DPL tanpa desa).
     *
     * @return Collection<int, object{kodept: string, nama_pt: string, nama_lokasi: string, nama_kecamatan: ?string, nama_desa: ?string}>
     */
    public function all(): Collection
    {
        // Latest location per mahasiswa, same as PenguranganSampahService::logQuery()
        $lokasiTerakhir = DB::table('mahasiswa_lokasi')
            ->whereNotNull('id_desa')
            ->groupBy('id_mahasiswa')
            ->selectRaw('id_mahasiswa, MAX(tahun) as tahun');

        // Same UNION as jumlah_pt in LokasiProgramSummary::all(), plus desa for mahasiswa
        $pasangan = DB::table('mahasiswa as m')
            ->leftJoinSub($lokasiTerakhir, 'lt', 'lt.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->leftJoin('mahasiswa_lokasi as ml', fn ($j) => $j->on('ml.id_mahasiswa', '=', 'lt.id_mahasiswa')->on('ml.tahun', '=', 'lt.tahun'))
            ->leftJoin('desa as md', 'md.id_desa', '=', 'ml.id_desa')
            ->whereNotNull('m.location_program')->whereNotNull('m.kodept')
            ->select('m.location_program', 'm.kodept', 'md.id_desa')
            ->union(DB::table('dpl')->whereNotNull('location_program')->whereNotNull('kodept')->select('location_program', 'kodept', DB::raw('NULL as id_desa')));

        return DB::query()->fromSub($pasangan, 'pasangan')
            ->join('lokasi_program', 'lokasi_program.id', '=', 'pasangan.location_program')
            ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'pasangan.kodept')
            ->leftJoin('desa as d', 'd.id_desa', '=', 'pasangan.id_desa')
            ->leftJoin('kecamatan as k', 'k.id_kecamatan', '=', 'd.id_kecamatan')
            ->get(['pasangan.kodept', 'pasangan.location_program', 'pasangan.id_desa', 'ref_satuanpendidikan.nm_lemb', 'lokasi_program.nama_lokasi', 'k.kecamatan', 'd.desa'])
            // Null-desa row only when the pair has no desa at all
            ->groupBy(fn ($row) => $row->kodept.'|'.$row->location_program)
            ->flatMap(function (Collection $rows) {
                $withDesa = $rows->whereNotNull('id_desa');

                return $withDesa->isNotEmpty() ? $withDesa : $rows->take(1);
            })
            ->map(fn ($row) => (object) [
                'kodept' => (string) $row->kodept,
                'nama_pt' => (string) ($row->nm_lemb ?? $row->kodept),
                'nama_lokasi' => (string) $row->nama_lokasi,
                'nama_kecamatan' => $row->kecamatan,
                'nama_desa' => $row->desa,
            ])
            ->sortBy([
                fn ($a, $b) => strnatcasecmp($a->nama_pt, $b->nama_pt),
                fn ($a, $b) => strnatcasecmp($a->nama_lokasi, $b->nama_lokasi),
                fn ($a, $b) => strnatcasecmp($a->nama_kecamatan ?? '', $b->nama_kecamatan ?? ''),
                fn ($a, $b) => strnatcasecmp($a->nama_desa ?? '', $b->nama_desa ?? ''),
            ])
            ->values();
    }

    // Population of lokasi status & list (alias m)
    public function lokasiBase(): Builder
    {
        return DB::table('mahasiswa as m')->whereNotNull('m.location_program')->whereNotNull('m.kodept');
    }

    /**
     * Mahasiswa sebaran (location_program & kodept terisi) yang sudah/belum set desa, tanpa cache.
     *
     * @return array{sudah: int, belum: int, total: int}
     */
    public function statusLokasi(): array
    {
        $row = $this->lokasiBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN '.self::SUDAH_LOKASI_SQL.' THEN 1 ELSE 0 END) as sudah')
            ->first();

        $total = (int) $row->total;
        $sudah = (int) $row->sudah;

        return ['sudah' => $sudah, 'belum' => $total - $sudah, 'total' => $total];
    }

    // Population of ketua status & list: distinct kodept of mahasiswa+DPL (UNION, like all()); use as subquery alias pt
    public function ketuaPtBase(): Builder
    {
        return $this->lokasiBase()->select('m.kodept')
            ->union(DB::table('dpl')->whereNotNull('location_program')->whereNotNull('kodept')->select('kodept'));
    }

    /**
     * PT di sebaran (UNION mahasiswa+DPL seperti all()) yang sudah/belum punya ketua kelompok (pj_desa), tanpa cache.
     *
     * @return array{sudah: int, belum: int, total: int}
     */
    public function statusKetua(): array
    {
        $row = DB::query()->fromSub($this->ketuaPtBase(), 'pt')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN '.self::SUDAH_KETUA_SQL.' THEN 1 ELSE 0 END) as sudah')
            ->first();

        $total = (int) $row->total;
        $sudah = (int) $row->sudah;

        return ['sudah' => $sudah, 'belum' => $total - $sudah, 'total' => $total];
    }

    /**
     * Nama lokasi per PT, turunan dari cached().
     *
     * @return array<string, string[]>
     */
    public function lokasiPerPt(): array
    {
        return $this->cached()
            ->groupBy('kodept')
            ->map(fn (Collection $rows) => $rows->pluck('nama_lokasi')->unique()->values()->all())
            ->all();
    }
}
