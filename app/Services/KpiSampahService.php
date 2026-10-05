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
        'jml_rw', 'jml_penduduk', 'jml_rumah', 'jml_rumah_memilah', 'timbulan',
        'organik_sumber', 'organik_metode_unit', 'organik_dlh',
        'anorganik_sumber', 'anorganik_metode_unit', 'pengurangan', 'belum_terkelola',
    ];

    // Kolom teks: saat diagregasi digabung (distinct) dengan "; "
    private const TEKS = [
        'organik_metode', 'organik_dlh_fasilitas', 'organik_dlh_lokasi',
        'anorganik_metode', 'anorganik_metode_lokasi', 'keterangan',
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

    // Capaian + sampah = satu data: baris bulan lama ikut pindah, sisa baris di bulan baru ditimpa
    public function simpanDariCapaian(string $email, ?string $bulanLama, string $bulan, array $data, ?string $idDesa): Kpisampah
    {
        $lama = $bulanLama ? Kpisampah::where('email', $email)->where('bulan', $bulanLama)->first() : null;
        $baru = Kpisampah::where('email', $email)->where('bulan', $bulan)
            ->when($lama, fn ($q) => $q->whereKeyNot($lama->getKey()))->first();
        if ($lama && $baru) {
            $baru->delete();
            $baru = null;
        }

        // id_desa baris lama tetap; hanya baris baru memakai desa ketua
        $row = $lama ?? $baru ?? new Kpisampah(['email' => $email, 'id_desa' => $idDesa]);
        $row->fill(self::hitung($data) + [
            'bulan' => $bulan,
            'id_pjdesa' => Pjdesa::where('email', $email)->value('id_pjdesa'),
        ]);
        $row->save();

        return $row;
    }

    public function hapusDariCapaian(string $email, string $bulan): void
    {
        Kpisampah::where('email', $email)->where('bulan', $bulan)->first()?->delete();
    }

    // Persentase & pengurangan selalu dihitung server, nilai dari klien diabaikan
    public static function hitung(array $data): array
    {
        $data['pengurangan'] = round((float) $data['organik_sumber'] + (float) $data['organik_dlh'] + (float) $data['anorganik_sumber'], 2);
        $data['belum_terkelola'] = round((float) $data['timbulan'] - $data['pengurangan'], 2);
        $data['persen_ketaatan'] = Kpisampah::persen($data['jml_rumah_memilah'], $data['jml_rumah']);
        $data['persen_pengurangan'] = Kpisampah::persen($data['pengurangan'], $data['timbulan']);

        return $data;
    }

    // 1 baris per (bulan, kelurahan, PT): angka dijumlah, persentase dihitung ulang dari total.
    // $perKetua (role PT, ditentukan controller): 1 baris per isian ketua + nama ketua
    public function detail(array $filter, bool $perKetua = false): Collection
    {
        if ($perKetua) {
            return $this->query($filter)
                ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
                ->orderByDesc('s.bulan')->orderBy('kc.kecamatan')->orderBy('d.desa')
                ->select('s.*', 'd.desa', 'd.id_kecamatan', 'kc.kecamatan', 'm.nama as nama_ketua', 'sp.nm_lemb as nama_pt')
                ->get();
        }

        return $this->withJumlah($this->query($filter))
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->groupBy('s.bulan', 's.id_desa', 'd.desa', 'd.id_kecamatan', 'kc.kecamatan', 'm.kodept', 'sp.nm_lemb')
            ->orderByDesc('s.bulan')->orderBy('kc.kecamatan')->orderBy('d.desa')
            ->selectRaw('s.bulan, s.id_desa, d.desa, d.id_kecamatan, kc.kecamatan, m.kodept, sp.nm_lemb as nama_pt')
            ->selectRaw(collect(self::TEKS)->map(fn ($k) => "GROUP_CONCAT(DISTINCT NULLIF(TRIM(s.$k), '') SEPARATOR '; ') as $k")->implode(', '))
            ->get()
            ->map(function ($row) {
                foreach (self::JUMLAH as $kolom) {
                    $row->$kolom = $row->{'total_'.$kolom};
                }

                return $this->isiPersen($row);
            });
    }

    /**
     * Total & persentase per kecamatan atau per kelurahan, dikunci id-nya.
     */
    public function totalPer(string $kolom, array $filter): Collection
    {
        $kolom = ['kecamatan' => 'd.id_kecamatan', 'desa' => 's.id_desa', 'kodept' => 'm.kodept', 'lokasi' => 'm.location_program'][$kolom];

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
    public function detailPerKecamatan(array $filter, bool $perKetua = false): Collection
    {
        $total = $this->rekapKecamatan($filter)->keyBy(fn ($r) => $r->bulan.'|'.$r->id_kecamatan);

        return $this->detail($filter, $perKetua)
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
     * $totalKeseluruhan: key `total` tidak difilter kecamatan (dipakai versi publik).
     */
    public function drilldown(array $filter, bool $totalKeseluruhan = false): array
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
                'id_lokasi' => $lokasi,
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
            'total' => $this->total($filterSampah + ['id_kecamatan' => $totalKeseluruhan ? null : $idKecamatan]),
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
     * Versi halaman publik (login): drilldown() + persen per lokasi program, total keseluruhan,
     * persen per PT di kelurahan, dan filter klaster (hijau > 20%) per baris tanpa hitung ulang angka.
     */
    public function drilldownPublik(array $filter): array
    {
        // kodept: dashboard PT/admin; halaman login tanpa kodept (semua PT)
        $kodept = $filter['kodept'] ?? null;
        $data = $this->drilldown(['bulan' => $filter['bulan'] ?? null, 'id_kecamatan' => $filter['id_kecamatan'] ?? null, 'id_desa' => $filter['id_desa'] ?? null, 'kodept' => $kodept], true);
        $bulan = $data['params']['bulan'];
        $klaster = $filter['klaster'] ?? null;
        $cocok = fn ($k) => ! $klaster || $k === $klaster;

        $persenLokasi = $bulan ? $this->totalPer('lokasi', ['bulan' => $bulan, 'kodept' => $kodept]) : collect();
        $persenPt = $bulan && $data['params']['desa']
            ? $this->totalPer('kodept', ['bulan' => $bulan, 'id_desa' => $data['params']['desa'], 'kodept' => $kodept])
            : collect();

        $data['params']['klaster'] = $klaster;
        $data['total_keseluruhan'] = $data['total'];

        $data['kecamatan'] = $data['kecamatan']->map(function ($lokasi) use ($persenLokasi, $cocok) {
            $lokasi->persen = $persenLokasi[$lokasi->id_lokasi]->persen_pengurangan ?? null;
            $lokasi->klaster = Kpisampah::klaster($lokasi->persen, true);
            $lokasi->kecamatan = $lokasi->kecamatan
                ->each(fn ($r) => $r->klaster = Kpisampah::klaster($r->persen, true))
                ->filter(fn ($r) => $cocok($r->klaster))->values();

            return $lokasi;
        })->filter(fn ($lokasi) => $lokasi->kecamatan->isNotEmpty())->values();

        $data['kelurahan'] = $data['kelurahan']
            ->each(fn ($r) => $r->klaster = Kpisampah::klaster($r->persen, true))
            ->filter(fn ($r) => $cocok($r->klaster))->values();

        $data['kelompok'] = $data['kelompok']->each(function ($r) use ($persenPt, $bulan) {
            // Detail hanya capaian bulan terpilih; ketua tanpa isian tampil baris kosong (detailPublik)
            $r->capaian = $r->capaian->filter(fn ($c) => $bulan && str_starts_with((string) $c->bulan, $bulan))->values();
            $r->persen_pt = $persenPt[$r->kodept]->persen_pengurangan ?? null;
            $r->klaster_pt = Kpisampah::klaster($r->persen_pt, true);
        })->filter(fn ($r) => $cocok($r->klaster_pt));

        // No. kontak semua ketua (1 query); email tidak ikut ke data publik
        $emailKetua = $data['kelompok']->flatMap(fn ($r) => $r->ketua_email->keys())->filter()->unique()->values();
        $phone = $emailKetua->isEmpty() ? collect()
            : DB::table('mahasiswa')->whereIn('email', $emailKetua)->whereNotNull('phone')->pluck('phone', 'email');

        $data['kelompok'] = $data['kelompok']
            // Hanya kolom yang dirender partial publik (tanpa email/nama_ketua) sebelum di-cache
            ->map(fn ($r) => (object) [
                'kodept' => $r->kodept,
                'nama_pt' => $r->nama_pt,
                'lokasi' => $r->lokasi,
                'jumlah_mahasiswa' => $r->jumlah_mahasiswa,
                'jumlah_dpl' => $r->jumlah_dpl,
                'ketua' => $r->ketua,
                'jumlah_ketua' => $r->ketua->count(),
                'persen_pt' => $r->persen_pt,
                'klaster_pt' => $r->klaster_pt,
                'capaian' => $r->capaian->map(fn ($c) => (object) [
                    'permasalahan' => $c->permasalahan,
                    'solusi' => $c->solusi,
                    'kendala' => $c->kendala,
                    'status_capaian' => $c->status_capaian,
                    'phone' => ($phone[$c->email] ?? null) ?: null,
                ]),
                'detail' => $this->detailPublik($r, $phone),
            ])->values();

        return $data;
    }

    // Baris detail PTS: 1 per capaian (+ nama & kontak pengisi), plus 1 baris kosong untuk ketua yang belum mengisi
    private function detailPublik(object $kelompok, Collection $phone): Collection
    {
        $baris = fn (?object $c, string $email) => (object) [
            'permasalahan' => $c?->permasalahan,
            'solusi' => $c?->solusi,
            'kendala' => $c?->kendala,
            'status_capaian' => $c?->status_capaian,
            'nama_ketua' => $kelompok->ketua_email[$email] ?? '-',
            'phone' => ($phone[$email] ?? null) ?: null,
        ];

        $pengisi = $kelompok->capaian->pluck('email')->unique();

        return $kelompok->capaian->toBase()->map(fn ($c) => $baris($c, (string) $c->email))
            ->concat($kelompok->ketua_email->keys()->diff($pengisi)->map(fn ($email) => $baris(null, $email)))
            ->sortBy('nama_ketua')->values();
    }

    /**
     * Export "Capaian Program" (admin): lokasi -> kecamatan (dari drilldownPublik) + SEMUA kelurahan berpenempatan
     * per lokasi|kecamatan. Ikut filter bulan & klaster, ambang strict seperti halaman publik.
     */
    public function capaianProgram(array $filter): array
    {
        $data = $this->drilldownPublik(['bulan' => $filter['bulan'] ?? null, 'klaster' => $filter['klaster'] ?? null]);
        $bulan = $data['params']['bulan'];
        $klaster = $data['params']['klaster'];
        $persenDesa = $bulan ? $this->totalPer('desa', ['bulan' => $bulan]) : collect();

        $kelurahan = $this->penempatanKetua(null)->whereNotNull('id_kecamatan')
            ->unique(fn ($r) => $r->location_program.'|'.$r->id_desa)->sortBy('desa')
            ->map(function ($r) use ($persenDesa) {
                $persen = $persenDesa[$r->id_desa]->persen_pengurangan ?? null;

                return (object) ['kunci' => $r->location_program.'|'.$r->id_kecamatan, 'desa' => $r->desa, 'persen' => $persen, 'klaster' => Kpisampah::klaster($persen, true)];
            })
            ->filter(fn ($r) => ! $klaster || $r->klaster === $klaster)
            ->groupBy('kunci');

        return ['bulan' => $bulan, 'klaster' => $klaster, 'lokasi' => $data['kecamatan'], 'kelurahan' => $kelurahan];
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
        ])->sortByDesc('persen');
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
        // Urut bulan terbaru
        $capaian = Kpicapaian::whereIn('email', $ketua->pluck('email'))
            ->orderByDesc('bulan')->orderByDesc('created_at')
            ->get();

        // 1 baris per PT di kelurahan ini; detail = gabungan capaian semua ketua PT tsb
        return $ketua->groupBy('kodept')->map(function (Collection $rows, $kodept) use ($namaPt, $mahasiswa, $dpl, $persen, $capaian) {
            $first = $rows->first();
            $emails = $rows->pluck('email')->all();

            return (object) [
                'kodept' => $kodept,
                'nama_pt' => $namaPt[$kodept] ?? $kodept,
                'lokasi' => $first->desa.', '.$first->kecamatan,
                'jumlah_mahasiswa' => (int) ($mahasiswa[$kodept] ?? 0),
                'jumlah_dpl' => $rows->pluck('location_program')->unique()
                    ->sum(fn ($lokasi) => (int) ($dpl[$kodept.'|'.$lokasi]->jumlah ?? 0)),
                'jumlah_ketua' => $rows->count(),
                'nama_ketua' => $rows->map(fn ($r) => $r->nama_ketua ?: $r->email)->implode(', '),
                // Distinct per email (akses ketua = terdaftar di pj_desa); nama kosong → '-'
                'ketua' => $rows->unique('email')->map(fn ($r) => $r->nama_ketua ?: '-')->sort()->values(),
                // email => nama ketua; dipakai drilldownPublik(), tidak ikut ke data publik
                'ketua_email' => $rows->unique('email')->mapWithKeys(fn ($r) => [$r->email => $r->nama_ketua ?: '-']),
                'persen' => $persen,
                'capaian' => $capaian->whereIn('email', $emails)->values(),
            ];
        })->sortBy('nama_pt')->values();
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
