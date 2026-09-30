<?php

namespace App\Services;

use App\Models\Kpicapaian;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap capaian KPI: 1 kelompok = 1 ketua (pj_desa), PT diambil dari mahasiswa.kodept ketua.
 * Hanya isian yang realisasinya terisi dan tindak lanjut Sudah Selesai yang dihitung.
 * Capaian kegiatan = min(rata-rata realisasi / target × 100, 100); tanpa data = null ("-").
 * Capaian KPI = rata-rata capaian kegiatannya yang punya data.
 *
 * Filter: lokasi (id lokasi_program), kodept (npsn), id_target (kegiatan).
 */
class KpiRekapService
{
    private const SELESAI_SQL = "c.status_capaian = 'Y' AND c.realisasi IS NOT NULL";

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
     * Satu baris per kegiatan: rata-rata realisasi kelompok yang selesai dibanding target.
     */
    public function rekapPerKegiatan(array $filter): Collection
    {
        $agregat = $this->withAgregat($this->withCapaian($this->kelompokQuery($filter), $filter))
            ->groupBy('t.id_target')
            ->selectRaw('t.id_target')
            ->get()->keyBy('id_target');

        return DB::table('kpi_target as t')
            ->leftJoin('kpi as k', 'k.id_kpi', '=', 't.id_kpi')
            ->when($filter['id_target'] ?? null, fn (Builder $q, $id) => $q->where('t.id_target', $id))
            ->orderBy('k.nama_kpi')->orderBy('t.kegiatan')
            ->select('t.id_target', 't.id_kpi', 'k.nama_kpi', 't.kegiatan', 't.target', 't.satuan')
            ->get()
            ->each(fn ($row) => $this->isiCapaian($row, $agregat[$row->id_target] ?? null));
    }

    /**
     * Satu baris per KPI: rata-rata capaian kegiatan yang sudah punya data.
     */
    public function rekapPerKpi(array $filter): Collection
    {
        return $this->rekapPerKegiatan($filter)
            ->groupBy('id_kpi')
            ->map(function (Collection $kegiatan) {
                $berdata = $kegiatan->whereNotNull('capaian');

                return (object) [
                    'id_kpi' => $kegiatan->first()->id_kpi,
                    'nama_kpi' => $kegiatan->first()->nama_kpi ?? '-',
                    'jumlah_kegiatan' => $kegiatan->count(),
                    'kegiatan_berdata' => $berdata->count(),
                    'capaian' => $berdata->isEmpty() ? null : round($berdata->avg('capaian'), 2),
                    'kegiatan' => $kegiatan->values(),
                ];
            })
            ->sortBy('nama_kpi')->values();
    }

    /**
     * Satu baris per lokasi program per PT, dikelompokkan per nama lokasi.
     */
    public function rekapPerLokasiPt(): Collection
    {
        $key = fn ($r) => $r->location_program.'|'.$r->kodept;

        $mahasiswa = $this->mahasiswaQuery([])
            ->whereNotNull('m.location_program')
            ->groupBy('m.location_program', 'm.kodept')
            ->selectRaw('m.location_program, m.kodept, COUNT(*) as jumlah')
            ->get()->keyBy($key);

        $wilayah = $this->mahasiswaQuery([])
            ->join('mahasiswa_lokasi as ml', 'ml.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->leftJoin('desa as d', 'd.id_desa', '=', 'ml.id_desa')
            ->groupBy('m.location_program', 'm.kodept')
            ->selectRaw('m.location_program, m.kodept, COUNT(DISTINCT d.id_kecamatan) as kecamatan, COUNT(DISTINCT ml.id_desa) as kelurahan')
            ->get()->keyBy($key);

        $kelompok = $this->kelompokQuery([])
            ->groupBy('m.location_program', 'm.kodept')
            ->selectRaw('m.location_program, m.kodept, COUNT(DISTINCT pj.id_pjdesa) as jumlah')
            ->get()->keyBy($key);

        $dpl = DB::table('dpl')
            ->whereNotNull('kodept')->whereNotNull('location_program')
            ->groupBy('location_program', 'kodept')
            ->selectRaw('location_program, kodept, COUNT(*) as jumlah')
            ->get()->keyBy($key);

        $keys = $mahasiswa->keys()->merge($dpl->keys())->unique();
        $namaLokasi = DB::table('lokasi_program')->pluck('nama_lokasi', 'id');
        $namaPt = DB::table('ref_satuanpendidikan')
            ->whereIn('npsn', $keys->map(fn ($k) => explode('|', $k, 2)[1])->unique())
            ->pluck('nm_lemb', 'npsn');

        return $keys->map(function ($k) use ($mahasiswa, $wilayah, $kelompok, $dpl, $namaLokasi, $namaPt) {
            [$lokasi, $kodept] = explode('|', $k, 2);

            return (object) [
                'nama_lokasi' => $namaLokasi[$lokasi] ?? 'Tanpa Lokasi Program',
                'nama_pt' => $namaPt[$kodept] ?? $kodept,
                'jumlah_mahasiswa' => (int) ($mahasiswa[$k]->jumlah ?? 0),
                'jumlah_kelompok' => (int) ($kelompok[$k]->jumlah ?? 0),
                'jumlah_dpl' => (int) ($dpl[$k]->jumlah ?? 0),
                'kecamatan' => (int) ($wilayah[$k]->kecamatan ?? 0),
                'kelurahan' => (int) ($wilayah[$k]->kelurahan ?? 0),
            ];
        })
            ->sortBy([['nama_lokasi', 'asc'], ['nama_pt', 'asc']])
            ->groupBy('nama_lokasi');
    }

    /**
     * Capaian per KPI untuk setiap lokasi program (daerah).
     */
    public function rekapPerDaerah(array $filter): Collection
    {
        return DB::table('lokasi_program')
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('id', $v))
            ->orderBy('nama_lokasi')
            ->get(['id', 'nama_lokasi'])
            ->map(fn ($lokasi) => (object) [
                'nama_lokasi' => $lokasi->nama_lokasi,
                'perKpi' => $this->rekapPerKpi(['lokasi' => $lokasi->id] + $filter),
            ]);
    }

    /**
     * Data chart KPI, satu series per lokasi program (null = belum ada data):
     * ringkasan = kategori KPI, detail = kategori kegiatan per KPI.
     */
    public function chartKpi(array $filter): array
    {
        $perLokasi = DB::table('lokasi_program')
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('id', $v))
            ->orderBy('nama_lokasi')
            ->get(['id', 'nama_lokasi'])
            ->mapWithKeys(fn ($l) => [$l->nama_lokasi => $this->rekapPerKegiatan(['lokasi' => $l->id] + $filter)->keyBy('id_target')]);

        if ($perLokasi->isEmpty()) {
            return ['ringkasan' => null, 'detail' => collect()];
        }

        $detail = $perLokasi->first()->values()
            ->groupBy('id_kpi')
            ->map(fn (Collection $kegiatan) => [
                'nama_kpi' => $kegiatan->first()->nama_kpi ?? '-',
                'kegiatan' => $kegiatan->pluck('kegiatan')->values(),
                'series' => $perLokasi->map(fn (Collection $rekap, $nama) => [
                    'name' => $nama,
                    'data' => $kegiatan->map(fn ($k) => $rekap[$k->id_target]->capaian)->values(),
                ])->values(),
            ])
            ->sortBy('nama_kpi')->values();

        // Capaian KPI per lokasi = rata-rata kegiatan yang punya data, sama dengan rekapPerKpi()
        $ringkasan = [
            'kpi' => $detail->pluck('nama_kpi'),
            'series' => $perLokasi->keys()->values()->map(fn ($nama, $i) => [
                'name' => $nama,
                'data' => $detail->map(function ($kpi) use ($i) {
                    $berdata = collect($kpi['series'][$i]['data'])->filter(fn ($v) => $v !== null);

                    return $berdata->isEmpty() ? null : round($berdata->avg(), 2);
                })->values(),
            ]),
        ];

        return ['ringkasan' => $ringkasan, 'detail' => $detail];
    }

    /**
     * Satu baris per PT per kegiatan.
     */
    public function rekapPerPt(array $filter): Collection
    {
        $rows = $this->withAgregat($this->withCapaian($this->kelompokQuery($filter), $filter))
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->groupBy('m.kodept', 'sp.nm_lemb', 't.id_target', 'k.nama_kpi', 't.kegiatan', 't.target', 't.satuan')
            ->orderBy('sp.nm_lemb')->orderBy('k.nama_kpi')->orderBy('t.kegiatan')
            ->selectRaw('m.kodept, sp.nm_lemb, t.id_target, k.nama_kpi, t.kegiatan, t.target, t.satuan')
            ->get();

        $mahasiswa = $this->mahasiswaQuery($filter)
            ->groupBy('m.kodept')
            ->selectRaw('m.kodept, COUNT(*) as jumlah')
            ->pluck('jumlah', 'kodept');

        return $rows->map(function ($row) use ($mahasiswa) {
            $row->nama_pt = $row->nm_lemb ?: $row->kodept;
            $row->jumlah_mahasiswa = (int) ($mahasiswa[$row->kodept] ?? 0);

            return $this->isiCapaian($row, $row);
        });
    }

    /**
     * Satu baris per PT: rata-rata capaian kegiatan yang sudah punya data.
     */
    public function rekapPerPtRingkas(array $filter): Collection
    {
        return $this->rekapPerPt($filter)
            ->groupBy('kodept')
            ->map(function (Collection $kegiatan) {
                $berdata = $kegiatan->whereNotNull('capaian');

                return (object) [
                    'kodept' => $kegiatan->first()->kodept,
                    'nama_pt' => $kegiatan->first()->nama_pt,
                    'jumlah_mahasiswa' => $kegiatan->first()->jumlah_mahasiswa,
                    'jumlah_kelompok' => $kegiatan->max('jumlah_kelompok'),
                    'jumlah_kegiatan' => $kegiatan->count(),
                    'kegiatan_berdata' => $berdata->count(),
                    'capaian' => $berdata->isEmpty() ? null : round($berdata->avg('capaian'), 2),
                    'kegiatan' => $kegiatan->values(),
                ];
            })
            ->sortBy('nama_pt')->values();
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
            ->each(fn ($row) => $row->capaian = Kpicapaian::persen($row->status_capaian, $row->realisasi, $row->target));
    }

    private function withAgregat(Builder $query): Builder
    {
        return $query
            ->selectRaw('COUNT(DISTINCT pj.id_pjdesa) as jumlah_kelompok')
            ->selectRaw('COUNT(DISTINCT CASE WHEN '.self::SELESAI_SQL.' THEN pj.id_pjdesa END) as jumlah_selesai')
            ->selectRaw('AVG(CASE WHEN '.self::SELESAI_SQL.' THEN c.realisasi END) as rata_realisasi');
    }

    private function isiCapaian(object $row, ?object $agg): object
    {
        $rata = $agg?->rata_realisasi;
        $capaian = Kpicapaian::capaianRata($rata, $row->target);

        $row->jumlah_kelompok = (int) ($agg->jumlah_kelompok ?? 0);
        $row->jumlah_selesai = (int) ($agg->jumlah_selesai ?? 0);
        $row->realisasi = $rata !== null ? round((float) $rata, 2) : null;
        $row->capaian = $capaian !== null ? round($capaian, 2) : null;

        return $row;
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
