<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan penempatan KKN per lokasi program: PT, kecamatan, kelurahan, mahasiswa, DPL, kelompok.
 * 1 kelompok = 1 ketua (pj_desa), PT diambil dari mahasiswa.kodept.
 * Capaian pengurangan sampah dihitung di PenguranganSampahService.
 *
 * Filter: lokasi (id lokasi_program), kodept (npsn).
 */
class RekapPenguranganSampahService
{
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
