<?php

namespace App\Services;

use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\Pjdesa;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data sampah bulanan per kelurahan yang diisi ketua kelompok.
 * Kelurahan ketua = pilihan lokasi mahasiswa tahun berjalan, fallback penempatan pj_desa.
 * Rekap kecamatan per bulan dijumlahkan dari kelurahannya; persentase dihitung dari total.
 *
 * Filter: bulan (Y-m), id_kecamatan, id_desa, kodept, lokasi (lokasi program ketua).
 */
class KpiSampahService
{
    private const JUMLAH = [
        'jml_rw_kbs', 'jml_rw_non_kbs', 'jml_rumah', 'jml_rumah_memilah', 'timbulan',
        'pengurangan_organik', 'pengurangan_anorganik', 'pengurangan', 'residu', 'jml_bank_sampah',
    ];

    public function desaKetua(string $email): ?string
    {
        $pj = Pjdesa::with('mahasiswa')->where('email', $email)->first();
        if (! $pj) {
            return null;
        }

        $pilihan = $pj->mahasiswa
            ? DB::table('mahasiswa_lokasi')
                ->where('id_mahasiswa', $pj->mahasiswa->id_mahasiswa)
                ->where('tahun', now()->year)
                ->value('id_desa')
            : null;

        return $pilihan ?? $pj->id_desa;
    }

    // Persentase & pengurangan selalu dihitung server, nilai dari klien diabaikan
    public static function hitung(array $data): array
    {
        $data['pengurangan'] = round((float) $data['pengurangan_organik'] + (float) $data['pengurangan_anorganik'], 2);
        $data['persen_ketaatan'] = Kpisampah::persen($data['jml_rumah_memilah'], $data['jml_rumah']);
        $data['persen_pengurangan'] = Kpisampah::persen($data['pengurangan'], $data['timbulan']);

        return $data;
    }

    public function detail(array $filter): Collection
    {
        return $this->query($filter)
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->orderByDesc('s.bulan')->orderBy('kc.kecamatan')->orderBy('d.desa')
            ->select('s.*', 'd.desa', 'd.id_kecamatan', 'kc.kecamatan', 'm.nama as nama_ketua', 'sp.nm_lemb as nama_pt')
            ->get();
    }

    /**
     * Total & persentase per kecamatan atau per kelurahan, dikunci id-nya.
     */
    public function totalPer(string $kolom, array $filter): Collection
    {
        $kolom = ['kecamatan' => 'd.id_kecamatan', 'desa' => 's.id_desa', 'kodept' => 'm.kodept'][$kolom];

        return $this->withJumlah($this->query($filter))
            ->whereNotNull($kolom)
            ->groupBy($kolom)
            ->selectRaw("$kolom as kunci")
            ->get()
            ->keyBy('kunci')
            ->map(fn ($row) => $this->isiPersen($row));
    }

    /**
     * Detail kelurahan dikelompokkan per bulan -> kecamatan, beserta total kecamatan (dijumlah dari kelurahannya).
     */
    public function detailPerKecamatan(array $filter): Collection
    {
        $total = $this->rekapKecamatan($filter)->keyBy(fn ($r) => $r->bulan.'|'.$r->id_kecamatan);

        return $this->detail($filter)
            ->groupBy('bulan')
            ->map(fn (Collection $perBulan, $bulan) => (object) [
                'bulan' => $bulan,
                'kecamatan' => $perBulan->sortBy('kecamatan')->groupBy('id_kecamatan')
                    ->map(fn (Collection $rows, $idKecamatan) => (object) [
                        'kecamatan' => $rows->first()->kecamatan,
                        'rows' => $rows->sortBy([['desa', 'asc'], ['nama_pt', 'asc']])->values(),
                        'total' => $total[$bulan.'|'.$idKecamatan] ?? null,
                    ])->values(),
            ])
            ->values();
    }

    /**
     * Satu baris per bulan per kecamatan.
     */
    public function rekapKecamatan(array $filter): Collection
    {
        return $this->withJumlah($this->query($filter))
            ->groupBy('s.bulan', 'kc.id_kecamatan', 'kc.kecamatan')
            ->orderByDesc('s.bulan')->orderBy('kc.kecamatan')
            ->selectRaw('s.bulan, kc.id_kecamatan, kc.kecamatan, COUNT(DISTINCT s.id_desa) as jml_kelurahan')
            ->get()
            ->map(fn ($row) => $this->isiPersen($row));
    }

    public function total(array $filter): object
    {
        return $this->isiPersen($this->withJumlah($this->query($filter))
            ->selectRaw('COUNT(*) as jml_data')
            ->first());
    }

    public function bulanTerakhir(?string $kodept = null): ?string
    {
        $bulan = $this->query(['kodept' => $kodept])->max('s.bulan');

        return $bulan ? Carbon::parse($bulan)->format('Y-m') : null;
    }

    public function bulanList(?string $kodept = null): Collection
    {
        return $this->query(['kodept' => $kodept])
            ->distinct()->orderByDesc('s.bulan')
            ->pluck('s.bulan')
            ->map(fn ($b) => Carbon::parse($b)->format('Y-m'));
    }

    /**
     * Laporan berjenjang: kecamatan per lokasi program -> kelurahan -> kelompok (ketua) di kelurahan.
     * Persentase = pengurangan / timbulan pada bulan terpilih (default bulan terakhir yang ada datanya).
     * Filter: bulan, kodept (PT dikunci), id_kecamatan, id_desa.
     */
    public function drilldown(array $filter): array
    {
        $filter['bulan'] = ($filter['bulan'] ?? null) ?: $this->bulanTerakhir($filter['kodept'] ?? null);
        $filterSampah = ['bulan' => $filter['bulan'], 'kodept' => $filter['kodept'] ?? null];
        $idKecamatan = $filter['id_kecamatan'] ?? null;
        $idDesa = $filter['id_desa'] ?? null;

        $penempatan = $this->penempatanKetua($filter['kodept'] ?? null);
        $persenKecamatan = $filter['bulan'] ? $this->totalPer('kecamatan', $filterSampah) : collect();
        $persenDesa = $filter['bulan'] ? $this->totalPer('desa', $filterSampah) : collect();
        $namaLokasi = DB::table('lokasi_program')->pluck('nama_lokasi', 'id');

        $kecamatan = $penempatan->whereNotNull('id_kecamatan')
            ->groupBy('location_program')
            ->map(fn (Collection $rows, $lokasi) => (object) [
                'nama_lokasi' => $namaLokasi[$lokasi] ?? 'Tanpa Lokasi Program',
                'kecamatan' => $rows->unique('id_kecamatan')->sortBy('kecamatan')->map(fn ($r) => (object) [
                    'id_kecamatan' => $r->id_kecamatan,
                    'kecamatan' => $r->kecamatan,
                    'persen' => $persenKecamatan[$r->id_kecamatan]->persen_pengurangan ?? null,
                ])->values(),
            ])
            ->sortBy('nama_lokasi')->values();

        $kelurahan = $idKecamatan
            ? $penempatan->where('id_kecamatan', $idKecamatan)->unique('id_desa')->sortBy('desa')
                ->map(fn ($r) => (object) [
                    'id_desa' => $r->id_desa,
                    'desa' => $r->desa,
                    'persen' => $persenDesa[$r->id_desa]->persen_pengurangan ?? null,
                ])->values()
            : collect();

        return [
            'params' => ['bulan' => $filter['bulan'], 'kecamatan' => $idKecamatan, 'desa' => $idDesa],
            'bulanList' => $this->bulanList($filter['kodept'] ?? null),
            'total' => $this->total($filterSampah + ['id_kecamatan' => $idKecamatan]),
            'kecamatan' => $kecamatan,
            'namaKecamatan' => $idKecamatan ? $penempatan->firstWhere('id_kecamatan', $idKecamatan)?->kecamatan : null,
            'kelurahan' => $kelurahan,
            'namaDesa' => $idDesa ? $penempatan->firstWhere('id_desa', $idDesa)?->desa : null,
            'kelompok' => $idDesa
                ? $this->kelompokDiKelurahan($penempatan->where('id_desa', $idDesa), $persenDesa[$idDesa]->persen_pengurangan ?? null)
                : collect(),
        ];
    }

    /**
     * Klaster tiap PT dari persentase pengurangan sampah gabungan kelurahannya (sesuai filter bulan/kecamatan).
     */
    public function klasterPt(array $filter): Collection
    {
        $totalPt = $this->totalPer('kodept', $filter);
        $namaPt = DB::table('ref_satuanpendidikan')->whereIn('npsn', $totalPt->keys())->pluck('nm_lemb', 'npsn');

        return $totalPt->map(fn ($r, $kodept) => (object) [
            'kodept' => $kodept,
            'nama_pt' => $namaPt[$kodept] ?? $kodept,
            'persen' => $r->persen_pengurangan,
            'klaster' => Kpisampah::klaster($r->persen_pengurangan),
        ])->sortBy('persen');
    }

    // Kelurahan tiap ketua kelompok: pilihan lokasi tahun berjalan, fallback penempatan pj_desa
    private function penempatanKetua(?string $kodept): Collection
    {
        $ketua = DB::table('pj_desa as pj')
            ->join('mahasiswa as m', 'm.email', '=', 'pj.email')
            ->leftJoin('mahasiswa_lokasi as ml', fn ($j) => $j->on('ml.id_mahasiswa', '=', 'm.id_mahasiswa')->where('ml.tahun', now()->year))
            ->whereNotNull('m.kodept')
            ->when($kodept, fn (Builder $q, $v) => $q->where('m.kodept', $v))
            ->selectRaw('pj.id_pjdesa, pj.email, m.nama as nama_ketua, m.location_program, m.kodept, COALESCE(ml.id_desa, pj.id_desa) as id_desa');

        return DB::query()->fromSub($ketua, 'k')
            ->join('desa as d', 'd.id_desa', '=', 'k.id_desa')
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->select('k.*', 'd.desa', 'd.id_kecamatan', 'kc.kecamatan')
            ->get();
    }

    private function kelompokDiKelurahan(Collection $ketua, ?float $persen): Collection
    {
        if ($ketua->isEmpty()) {
            return collect();
        }

        $idDesa = $ketua->first()->id_desa;
        $kodept = $ketua->pluck('kodept')->unique();

        $namaPt = DB::table('ref_satuanpendidikan')->whereIn('npsn', $kodept)->pluck('nm_lemb', 'npsn');
        $mahasiswa = DB::table('mahasiswa as m')
            ->join('mahasiswa_lokasi as ml', 'ml.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->where('ml.tahun', now()->year)->where('ml.id_desa', $idDesa)
            ->whereIn('m.kodept', $kodept)
            ->groupBy('m.kodept')
            ->selectRaw('m.kodept, COUNT(*) as jumlah')
            ->pluck('jumlah', 'kodept');
        $dpl = DB::table('dpl')->whereIn('kodept', $kodept)
            ->groupBy('kodept', 'location_program')
            ->selectRaw('kodept, location_program, COUNT(*) as jumlah')
            ->get()->keyBy(fn ($r) => $r->kodept.'|'.$r->location_program);
        $capaian = Kpicapaian::with('kpi:id_kpi,nama_kpi')
            ->whereIn('email', $ketua->pluck('email'))
            ->get()->groupBy('email');

        return $ketua->map(fn ($r) => (object) [
            'id_pjdesa' => $r->id_pjdesa,
            'nama_pt' => $namaPt[$r->kodept] ?? $r->kodept,
            'lokasi' => $r->desa.', '.$r->kecamatan,
            'jumlah_mahasiswa' => (int) ($mahasiswa[$r->kodept] ?? 0),
            'jumlah_dpl' => (int) ($dpl[$r->kodept.'|'.$r->location_program]->jumlah ?? 0),
            'nama_ketua' => $r->nama_ketua ?: $r->email,
            'persen' => $persen,
            'capaian' => ($capaian[$r->email] ?? collect())->sortBy(fn ($c) => $c->kpi->nama_kpi ?? '')->values(),
        ])->sortBy('nama_pt')->values();
    }

    private function query(array $filter): Builder
    {
        return DB::table('kpi_sampah as s')
            ->join('desa as d', 'd.id_desa', '=', 's.id_desa')
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 's.email')
            ->when($filter['bulan'] ?? null, fn (Builder $q, $v) => $q->where('s.bulan', $v.'-01'))
            ->when($filter['id_kecamatan'] ?? null, fn (Builder $q, $v) => $q->where('d.id_kecamatan', $v))
            ->when($filter['id_desa'] ?? null, fn (Builder $q, $v) => $q->where('s.id_desa', $v))
            ->when($filter['kodept'] ?? null, fn (Builder $q, $v) => $q->where('m.kodept', $v))
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('m.location_program', $v))
            // Daftar PT hasil pilihan klaster; daftar kosong = tidak ada data
            ->when(array_key_exists('kodept_in', $filter), fn (Builder $q) => $q->whereIn('m.kodept', $filter['kodept_in']));
    }

    private function withJumlah(Builder $query): Builder
    {
        foreach (self::JUMLAH as $kolom) {
            $query->selectRaw("COALESCE(SUM(s.$kolom), 0) as total_$kolom");
        }

        return $query;
    }

    private function isiPersen(object $row): object
    {
        $row->persen_ketaatan = Kpisampah::persen($row->total_jml_rumah_memilah, $row->total_jml_rumah);
        $row->persen_pengurangan = Kpisampah::persen($row->total_pengurangan, $row->total_timbulan);

        return $row;
    }
}
