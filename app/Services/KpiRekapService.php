<?php

namespace App\Services;

use App\Models\Kpicapaian;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap capaian KPI: 1 kelompok = 1 ketua (pj_desa), PT diambil dari mahasiswa.kodept ketua.
 * Capaian kelompok = 100% bila tindak lanjut Sudah Selesai, selain itu (termasuk belum mengisi) 0%.
 * Capaian PT = rata-rata capaian seluruh kelompoknya.
 *
 * Filter: lokasi (id lokasi_program), kodept (npsn), id_target (kegiatan).
 */
class KpiRekapService
{
    private const PERSEN_SQL = "CASE WHEN c.status_capaian = 'Y' THEN 100 ELSE 0 END";

    public function summary(array $filter): array
    {
        $total = $this->lokasiTable($filter)['total'];

        return [
            'jumlah_pt' => $total['pt'],
            'total_kecamatan' => $total['kecamatan'],
            'total_kelurahan' => $total['kelurahan'],
            'total_mahasiswa' => $total['mahasiswa'],
            'total_dpl' => $total['dpl'],
            'total_kelompok' => $total['kelompok'],
        ];
    }

    /**
     * Satu baris per lokasi program dari penempatan mahasiswa (mahasiswa_lokasi),
     * ditambah jumlah mahasiswa yang belum memilih lokasi dan baris total.
     */
    public function lokasiTable(array $filter): array
    {
        $placed = fn (Builder $q) => $q->whereExists(fn ($s) => $s->from('mahasiswa_lokasi as ml')->whereColumn('ml.id_mahasiswa', 'm.id_mahasiswa'));

        $perLokasi = $placed($this->mahasiswaQuery($filter))
            ->groupBy('m.location_program')
            ->selectRaw('m.location_program, COUNT(*) as jumlah, COUNT(DISTINCT m.kodept) as pt')
            ->get()->keyBy('location_program');
        $mahasiswa = $perLokasi->map(fn ($r) => $r->jumlah);

        $wilayahQuery = fn () => $this->mahasiswaQuery($filter)
            ->join('mahasiswa_lokasi as ml', 'ml.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->leftJoin('desa as d', 'd.id_desa', '=', 'ml.id_desa');
        $wilayah = $wilayahQuery()
            ->groupBy('m.location_program')
            ->selectRaw('m.location_program, COUNT(DISTINCT d.id_kecamatan) as kecamatan, COUNT(DISTINCT ml.id_desa) as kelurahan')
            ->get()->keyBy('location_program');

        $kelompok = $this->kelompokQuery($filter)
            ->groupBy('m.location_program')
            ->selectRaw('m.location_program, COUNT(DISTINCT pj.id_pjdesa) as jumlah')
            ->pluck('jumlah', 'location_program');

        $dpl = DB::table('dpl')
            ->whereNotNull('kodept')
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('location_program', $v))
            ->when($filter['kodept'] ?? null, fn (Builder $q, $v) => $q->where('kodept', $v))
            ->groupBy('location_program')
            ->selectRaw('location_program, COUNT(*) as jumlah')
            ->pluck('jumlah', 'location_program');

        $ids = $mahasiswa->keys()->merge($kelompok->keys())->merge($dpl->keys())->unique();
        $namaLokasi = DB::table('lokasi_program')->whereIn('id', $ids->filter())->pluck('nama_lokasi', 'id');

        $rows = $ids->map(fn ($id) => [
            'lokasi' => $namaLokasi[$id] ?? 'Tanpa Lokasi Program',
            'pt' => (int) ($perLokasi[$id]->pt ?? 0),
            'kecamatan' => (int) ($wilayah[$id]->kecamatan ?? 0),
            'kelurahan' => (int) ($wilayah[$id]->kelurahan ?? 0),
            'mahasiswa' => (int) ($mahasiswa[$id] ?? 0),
            'dpl' => (int) ($dpl[$id] ?? 0),
            'kelompok' => (int) ($kelompok[$id] ?? 0),
        ])->sortBy('lokasi')->values();

        $belumLokasi = $this->mahasiswaQuery($filter)
            ->whereNotExists(fn ($s) => $s->from('mahasiswa_lokasi as ml')->whereColumn('ml.id_mahasiswa', 'm.id_mahasiswa'))
            ->count();

        return [
            'rows' => $rows,
            'belum_lokasi' => $belumLokasi,
            'total' => [
                'pt' => $this->mahasiswaQuery($filter)->distinct()->count('m.kodept'),
                'kecamatan' => $wilayahQuery()->whereNotNull('d.id_kecamatan')->distinct()->count('d.id_kecamatan'),
                'kelurahan' => $wilayahQuery()->whereNotNull('ml.id_desa')->distinct()->count('ml.id_desa'),
                'mahasiswa' => $rows->sum('mahasiswa') + $belumLokasi,
                'dpl' => $rows->sum('dpl'),
                'kelompok' => $rows->sum('kelompok'),
            ],
        ];
    }

    /**
     * Satu baris per kegiatan: rata-rata capaian seluruh kelompok (yang belum mengisi = 0%).
     */
    public function rekapPerKegiatan(array $filter): Collection
    {
        $agregat = $this->withCapaian($this->kelompokQuery($filter), $filter)
            ->groupBy('t.id_target')
            ->selectRaw('t.id_target, COUNT(DISTINCT pj.id_pjdesa) as jumlah_kelompok, COUNT(c.id_capaian) as jumlah_mengisi')
            ->selectRaw('SUM('.self::PERSEN_SQL.') as total_persen')
            ->get()->keyBy('id_target');

        return DB::table('kpi_target as t')
            ->leftJoin('kpi as k', 'k.id_kpi', '=', 't.id_kpi')
            ->when($filter['id_target'] ?? null, fn (Builder $q, $id) => $q->where('t.id_target', $id))
            ->orderBy('k.nama_kpi')->orderBy('t.kegiatan')
            ->select('t.id_target', 'k.nama_kpi', 't.kegiatan', 't.target', 't.satuan')
            ->get()
            ->each(function ($row) use ($agregat) {
                $agg = $agregat[$row->id_target] ?? null;
                $n = (int) ($agg->jumlah_kelompok ?? 0);
                $row->jumlah_kelompok = $n;
                $row->jumlah_mengisi = (int) ($agg->jumlah_mengisi ?? 0);
                $row->capaian = $n > 0 ? round($agg->total_persen / $n, 2) : 0.0;
            });
    }

    /**
     * Satu baris per PT per kegiatan.
     */
    public function rekapPerPt(array $filter): Collection
    {
        $rows = $this->withCapaian($this->kelompokQuery($filter), $filter)
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->groupBy('m.kodept', 'sp.nm_lemb', 't.id_target', 'k.nama_kpi', 't.kegiatan', 't.target', 't.satuan')
            ->orderBy('sp.nm_lemb')->orderBy('k.nama_kpi')->orderBy('t.kegiatan')
            ->selectRaw('m.kodept, sp.nm_lemb, t.id_target, k.nama_kpi, t.kegiatan, t.target, t.satuan')
            ->selectRaw('COUNT(DISTINCT pj.id_pjdesa) as jumlah_kelompok')
            ->selectRaw('COUNT(c.id_capaian) as jumlah_mengisi')
            ->selectRaw('SUM(COALESCE(c.realisasi, 0)) as total_realisasi')
            ->selectRaw('SUM('.self::PERSEN_SQL.') as total_persen')
            ->get();

        $mahasiswa = $this->mahasiswaQuery($filter)
            ->groupBy('m.kodept')
            ->selectRaw('m.kodept, COUNT(*) as jumlah')
            ->pluck('jumlah', 'kodept');

        return $rows->map(function ($row) use ($mahasiswa) {
            $n = (int) $row->jumlah_kelompok;
            $row->nama_pt = $row->nm_lemb ?: $row->kodept;
            $row->jumlah_mahasiswa = (int) ($mahasiswa[$row->kodept] ?? 0);
            $row->realisasi = $n > 0 ? round($row->total_realisasi / $n, 2) : 0.0;
            $row->capaian = $n > 0 ? round($row->total_persen / $n, 2) : 0.0;

            return $row;
        });
    }

    /**
     * Isian per ketua kelompok per kegiatan, termasuk yang belum mengisi.
     */
    public function isianKelompok(array $filter): Collection
    {
        return $this->withCapaian($this->kelompokQuery($filter), $filter)
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'm.location_program')
            ->orderBy('m.nama')->orderBy('k.nama_kpi')->orderBy('t.kegiatan')
            ->select('m.nama', 'pj.email', 'lp.nama_lokasi', 'kc.kecamatan', 'd.desa', 'k.nama_kpi', 't.kegiatan', 't.target', 't.satuan', 'c.realisasi', 'c.status_capaian', 'c.id_capaian')
            ->get()
            ->each(fn ($row) => $row->capaian = Kpicapaian::persen($row->status_capaian));
    }

    // Setiap kelompok dipasangkan dengan setiap kegiatan agar yang belum mengisi tetap terhitung
    private function withCapaian(Builder $kelompok, array $filter): Builder
    {
        return $kelompok
            ->crossJoin('kpi_target as t')
            ->leftJoin('kpi_capaian as c', function ($join) {
                $join->on('c.email', '=', 'pj.email')->on('c.id_target', '=', 't.id_target');
            })
            ->leftJoin('kpi as k', 'k.id_kpi', '=', 't.id_kpi')
            ->when($filter['id_target'] ?? null, fn (Builder $q, $id) => $q->where('t.id_target', $id));
    }

    private function kelompokQuery(array $filter): Builder
    {
        return DB::table('pj_desa as pj')
            ->join('mahasiswa as m', 'm.email', '=', 'pj.email')
            ->leftJoin('desa as d', 'd.id_desa', '=', 'pj.id_desa')
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('m.location_program', $v))
            ->when($filter['kodept'] ?? null, fn (Builder $q, $v) => $q->where('m.kodept', $v));
    }

    private function mahasiswaQuery(array $filter): Builder
    {
        return DB::table('mahasiswa as m')
            ->whereNotNull('m.kodept')
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('m.location_program', $v))
            ->when($filter['kodept'] ?? null, fn (Builder $q, $v) => $q->where('m.kodept', $v));
    }
}
