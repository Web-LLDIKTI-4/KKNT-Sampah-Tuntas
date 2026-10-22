<?php

namespace App\Services;

use App\Models\CapaianKegiatan;
use App\Models\PenguranganSampah;
use App\Models\Pjdesa;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap sampah dari Pendataan Sampah Penduduk, per bulan + kelurahan.
 * Kelurahan log = lokasi mahasiswa (mahasiswa_lokasi) tahun terbaru.
 * Persentase dihitung dari total: ketaatan = memilah/rumah, penurunan = terkelola/dihasilkan,
 * penurunan timbulan (= capaian/persen_pengurangan) = 1 - ΣN / Σ(koef × jiwa).
 *
 * Filter: bulan (Y-m), tahun, id_kecamatan, id_desa, kodept, lokasi, kodept_in, bulan_terakhir_desa (bool).
 */
class PenguranganSampahService
{
    // Memo penempatanData() per kodept
    private array $penempatanData = [];

    // Memo dukungan REGEXP_REPLACE di server DB
    private ?bool $regexpReplace = null;

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
        $data['pengurangan'] = round((float) $data['organik_sumber'] + (float) $data['organik_dlh'] + (float) $data['anorganik_sumber'], 2);
        $data['belum_terkelola'] = round((float) $data['timbulan'] - $data['pengurangan'], 2);
        $data['persen_ketaatan'] = PenguranganSampah::persen($data['jml_rumah_memilah'], $data['jml_rumah']);
        $data['persen_pengurangan'] = PenguranganSampah::persen($data['pengurangan'], $data['timbulan']);

        return $data;
    }

    // 1 baris per (bulan, kelurahan, PT): angka dijumlah, persentase dihitung ulang dari total.
    // Kelurahan mahasiswa = mahasiswa_lokasi tahun terbaru (sama dengan sumber pendataan pemilahan)
    public function desaMahasiswa(string $email): ?string
    {
        return DB::table('mahasiswa_lokasi as ml')
            ->join('mahasiswa as m', 'm.id_mahasiswa', '=', 'ml.id_mahasiswa')
            ->where('m.email', $email)
            ->whereNotNull('ml.id_desa')
            ->orderByDesc('ml.tahun')
            ->value('ml.id_desa');
    }

    /**
     * Format LLDIKTI: bulan -> kecamatan -> kelurahan (baris A–L), urut bulan terbaru.
     */
    public function rekapLldikti(array $filter): Collection
    {
        return $this->withJumlah($this->query($filter))
            ->groupBy('s.bulan', 's.id_desa', 'd.desa', 'kc.id_kecamatan', 'kc.kecamatan')
            ->orderByDesc('s.bulan')->orderBy('kc.kecamatan')->orderBy('d.desa')
            ->selectRaw('s.bulan, s.id_desa, d.desa, kc.id_kecamatan, kc.kecamatan')
            ->get()
            ->map(fn ($row) => $this->isiPersen($row))
            ->groupBy('bulan')
            ->map(fn (Collection $perBulan, $bulan) => (object) [
                'bulan' => substr($bulan, 0, 7),
                'nama_bulan' => Carbon::parse($bulan)->translatedFormat('F Y'),
                'kecamatan' => $perBulan->groupBy(fn ($r) => (string) $r->id_kecamatan)
                    ->map(fn (Collection $rows) => (object) [
                        'id_kecamatan' => $rows->first()->id_kecamatan,
                        'kecamatan' => $rows->first()->kecamatan,
                        'desa' => $rows->values(),
                    ])->values(),
            ])
            ->values();
    }

    /**
     * Total & persentase per kecamatan atau per kelurahan, dikunci id-nya.
     */
    public function totalPer(string $kolom, array $filter): Collection
    {
        $kolom = ['kecamatan' => 'd.id_kecamatan', 'desa' => 's.id_desa', 'kodept' => 's.kodept', 'lokasi' => 's.location_program'][$kolom];

        return $this->withPenurunan($this->withJumlah($this->query($filter)))
            ->whereNotNull($kolom)
            ->groupBy($kolom)
            ->selectRaw("$kolom as kunci")
            ->get()
            ->keyBy('kunci')
            ->map(fn ($row) => $this->isiPenurunan($this->isiPersen($row)));
    }

    /**
     * totalPer('desa') + totalPer('kecamatan') dalam satu query: kecamatan = jumlah baris desanya
     * (kunci rumah memuat id desa, jadi semua kolom agregat aditif antar desa).
     *
     * @return array{desa: Collection, kecamatan: Collection}
     */
    public function totalDesaKecamatan(array $filter): array
    {
        $desa = $this->withPenurunan($this->withJumlah($this->query($filter)))
            ->groupBy('s.id_desa', 'd.id_kecamatan')
            ->selectRaw('s.id_desa as kunci, d.id_kecamatan')
            ->get();

        $agregat = ['total_jml_rumah', 'total_jml_rumah_memilah', 'total_organik', 'total_anorganik', 'total_residu', 'total_jiwa', 'dihasilkan_berjiwa', 'potensi_timbulan'];
        $kecamatan = $desa->whereNotNull('id_kecamatan')
            ->groupBy('id_kecamatan')
            ->map(function (Collection $rows, $kunci) use ($agregat) {
                $row = (object) ['kunci' => $kunci];
                foreach ($agregat as $kolom) {
                    $row->{$kolom} = $rows->sum(fn ($r) => (float) $r->{$kolom});
                }

                return $this->isiPenurunan($this->isiPersen($row));
            });

        return [
            'desa' => $desa->keyBy('kunci')->map(fn ($row) => $this->isiPenurunan($this->isiPersen($row))),
            'kecamatan' => $kecamatan,
        ];
    }

    public function total(array $filter): object
    {
        return $this->isiPenurunan($this->isiPersen($this->withPenurunan($this->withJumlah($this->query($filter)))
            ->selectRaw('COUNT(*) as jml_data, COUNT(DISTINCT s.id_desa) as jml_desa')
            ->first()));
    }

    /**
     * Data Sampah: 1 baris per log + flag rumah (jml_rumah = 1 hanya di log pertama kunci rumah,
     * jml_rumah_memilah = 1 di baris yang sama bila rumah itu memilah di bulan tsb), dihitung dalam set terfilter.
     * Urut: lokasi program → bulan terbaru → tanggal → kecamatan → desa → id (stabil untuk paging/lazy).
     */
    public function dataSampahQuery(array $filter): Builder
    {
        $urut = "CONCAT(s.tanggal, '|', COALESCE(s.created_at, ''), '|', s.id_pendataan)";
        // Tanpa window function: log pertama = MIN(sort key) per kunci rumah
        $rumah = $this->query($filter)
            ->groupBy('s.rumah')
            ->selectRaw("s.rumah, MIN({$urut}) as pertama, MAX(s.memilah) as memilah");

        return $this->query($filter)
            ->joinSub($rumah, 'r', 'r.rumah', '=', 's.rumah')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 's.location_program')
            ->selectRaw('s.id_pendataan, s.location_program as lokasi_id, lp.nama_lokasi, lp.koefisien_timbulan_sampah as koefisien')
            ->selectRaw('s.bulan, FLOOR((DAY(s.tanggal) - 1) / 7) + 1 as minggu, s.tanggal, kc.kecamatan, d.desa')
            ->selectRaw('s.nama_kepala_keluarga, s.alamat_rumah, s.jiwa, s.organik_kg, s.anorganik_kg, s.residu_kg')
            ->selectRaw("CASE WHEN r.pertama = {$urut} THEN 1 ELSE 0 END as is_pertama")
            ->selectRaw("CASE WHEN r.pertama = {$urut} AND r.memilah = 1 THEN 1 ELSE 0 END as is_memilah")
            ->orderByRaw('lp.nama_lokasi IS NULL')->orderBy('lp.nama_lokasi')->orderBy('s.location_program')
            ->orderByDesc('s.bulan')->orderBy('s.tanggal')->orderBy('kc.kecamatan')->orderBy('d.desa')->orderBy('s.id_pendataan');
    }

    /**
     * Subtotal Data Sampah (Σ baris dataSampahQuery): lokasi → bulan (terbaru dulu) → minggu (hanya $mingguan).
     */
    public function dataSampahSubtotal(array $filter, bool $mingguan): Collection
    {
        $rows = DB::query()->fromSub($this->dataSampahQuery($filter)->reorder(), 'x')
            ->groupBy('x.lokasi_id', 'x.nama_lokasi', 'x.koefisien', 'x.bulan', 'x.minggu')
            ->selectRaw('x.lokasi_id, x.nama_lokasi, x.koefisien, x.bulan, x.minggu')
            ->selectRaw('SUM(x.is_pertama) as jml_rumah, SUM(x.is_memilah) as jml_rumah_memilah')
            ->selectRaw('COALESCE(SUM(x.organik_kg), 0) as organik, COALESCE(SUM(x.anorganik_kg), 0) as anorganik, COALESCE(SUM(x.residu_kg), 0) as residu')
            ->selectRaw('COALESCE(SUM(x.jiwa), 0) as jiwa')
            ->selectRaw('COALESCE(SUM(CASE WHEN x.jiwa > 0 THEN '.self::dihasilkanSql('x').' END), 0) as dihasilkan_berjiwa')
            ->orderByRaw('x.nama_lokasi IS NULL')->orderBy('x.nama_lokasi')->orderBy('x.lokasi_id')
            ->orderByDesc('x.bulan')->orderBy('x.minggu')
            ->get();

        return $rows->groupBy(fn ($r) => (string) $r->lokasi_id)->map(function (Collection $perLokasi) use ($mingguan) {
            $first = $perLokasi->first();
            $koef = $first->koefisien === null ? null : (float) $first->koefisien;

            return (object) [
                'id' => $first->lokasi_id,
                'nama' => $first->nama_lokasi ?? 'Tanpa Lokasi Program',
                'koefisien' => $koef,
                'total' => $this->angkaSubtotal($perLokasi, $koef),
                'bulan' => $perLokasi->groupBy('bulan')->map(fn (Collection $perBulan, $bulan) => (object) [
                    'bulan' => substr($bulan, 0, 7),
                    'nama_bulan' => Carbon::parse($bulan)->translatedFormat('F Y'),
                    'subtotal' => $this->angkaSubtotal($perBulan, $koef),
                    'minggu' => $mingguan ? $perBulan->map(fn ($r) => (object) [
                        'minggu' => (int) $r->minggu,
                        'label' => self::labelMinggu((int) $r->minggu, $bulan),
                        'subtotal' => $this->angkaSubtotal(collect([$r]), $koef),
                    ])->values() : collect(),
                ])->values(),
            ];
        })->values();
    }

    // Kolom hitung satu baris log (H, M, N, O, P); null = tampil "-"
    public static function barisLog(object $r, ?float $koef): array
    {
        $rumah = (int) $r->is_pertama;
        $memilah = (int) $r->is_memilah;
        $organik = round((float) $r->organik_kg, 2);
        $anorganik = round((float) $r->anorganik_kg, 2);
        $residu = round((float) $r->residu_kg, 2);
        $terkelola = round($organik + $anorganik, 2);
        $dihasilkan = round($terkelola + $residu, 2);
        $jiwa = $r->jiwa === null ? null : (int) $r->jiwa;

        return [
            'jml_rumah' => $rumah,
            'jml_rumah_memilah' => $memilah,
            'persen_ketaatan' => PenguranganSampah::persen($memilah, $rumah),
            'organik' => $organik,
            'anorganik' => $anorganik,
            'residu' => $residu,
            'jml_jiwa' => $jiwa,
            'total_terkelola' => $terkelola,
            'total_dihasilkan' => $dihasilkan,
            'persen_pengelolaan' => PenguranganSampah::persen($terkelola, $dihasilkan),
            'persen_penurunan_timbulan' => self::penurunanTimbulan($dihasilkan, (float) $koef * (int) $jiwa),
        ];
    }

    // Minggu per 7 tanggal: 1 (1–7) … 5 (29–akhir bulan)
    public static function labelMinggu(int $minggu, string $bulan): string
    {
        $akhir = min($minggu * 7, Carbon::parse($bulan)->daysInMonth);

        return 'Minggu '.$minggu.' ('.(($minggu - 1) * 7 + 1).'–'.$akhir.')';
    }

    // Total sampah dihasilkan (N = organik + anorganik + residu) satu baris log pada alias tabel
    private static function dihasilkanSql(string $alias): string
    {
        return "COALESCE({$alias}.organik_kg, 0) + COALESCE({$alias}.anorganik_kg, 0) + COALESCE({$alias}.residu_kg, 0)";
    }

    // (1 - dihasilkan / (koef × jiwa)) × 100; null bila potensi 0 (koef/jiwa kosong). Negatif apa adanya
    private static function penurunanTimbulan(float $dihasilkan, float $potensi): ?float
    {
        return $potensi > 0 ? round((1 - $dihasilkan / $potensi) * 100, 2) : null;
    }

    // Angka subtotal dari baris agregat dataSampahSubtotal(); persen dihitung ulang dari jumlah
    private function angkaSubtotal(Collection $rows, ?float $koef): object
    {
        $rumah = (int) $rows->sum('jml_rumah');
        $memilah = (int) $rows->sum('jml_rumah_memilah');
        $organik = round((float) $rows->sum('organik'), 2);
        $anorganik = round((float) $rows->sum('anorganik'), 2);
        $residu = round((float) $rows->sum('residu'), 2);
        $terkelola = round($organik + $anorganik, 2);
        $dihasilkan = round($terkelola + $residu, 2);
        $jiwa = (int) $rows->sum('jiwa');

        return (object) [
            'jml_rumah' => $rumah,
            'jml_rumah_memilah' => $memilah,
            'persen_ketaatan' => PenguranganSampah::persen($memilah, $rumah),
            'organik' => $organik,
            'anorganik' => $anorganik,
            'residu' => $residu,
            'jml_jiwa' => $jiwa,
            'total_terkelola' => $terkelola,
            'total_dihasilkan' => $dihasilkan,
            'persen_pengelolaan' => PenguranganSampah::persen($terkelola, $dihasilkan),
            'persen_penurunan_timbulan' => self::penurunanTimbulan((float) $rows->sum('dihasilkan_berjiwa'), (float) $koef * $jiwa),
        ];
    }

    public function bulanTerakhir(?string $kodept = null, ?int $tahun = null): ?string
    {
        $bulan = $this->query(['kodept' => $kodept, 'tahun' => $tahun])->max('s.tanggal');

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
     * Laporan berjenjang: kecamatan per lokasi program -> kelurahan -> PT di kelurahan (basis data pendataan).
     * Persentase = penurunan timbulan (1 - ΣN / Σ(koef × jiwa)) pada bulan terpilih (default bulan terakhir yang ada datanya).
     * Filter: bulan, kodept (PT dikunci), id_kecamatan, id_desa.
     * $totalKeseluruhan: key `total` tidak difilter kecamatan (dipakai versi publik).
     */
    public function drilldown(array $filter, bool $totalKeseluruhan = false): array
    {
        $filter['bulan'] = ($filter['bulan'] ?? null) ?: $this->bulanTerakhir($filter['kodept'] ?? null);
        $filterSampah = ['bulan' => $filter['bulan'], 'kodept' => $filter['kodept'] ?? null];
        $idKecamatan = $filter['id_kecamatan'] ?? null;
        $idDesa = $filter['id_desa'] ?? null;

        $penempatan = $this->penempatanData($filter['kodept'] ?? null);
        $persenKecamatan = $filter['bulan'] ? $this->totalPer('kecamatan', $filterSampah) : collect();
        $persenDesa = $filter['bulan'] ? $this->totalPer('desa', $filterSampah) : collect();
        $namaLokasi = DB::table('lokasi_program')->pluck('nama_lokasi', 'id');

        $kecamatan = $penempatan->groupBy('location_program')
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

        $dataDesa = $idDesa ? $penempatan->where('id_desa', $idDesa) : collect();
        $kelurahan = $idKecamatan
            ? $penempatan->where('id_kecamatan', $idKecamatan)->unique('id_desa')->sortBy('desa')
                ->map(fn ($r) => (object) [
                    'id_desa' => $r->id_desa,
                    'desa' => $r->desa,
                    'baseline' => $r->baseline !== null ? (float) $r->baseline : null,
                    'satuan_baseline' => $r->satuan_baseline ?? '%',
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
            // Ketua hanya dicari bila kelurahan muncul dari data
            'kelompok' => $dataDesa->isNotEmpty()
                ? $this->kelompokDiKelurahan($dataDesa, $this->penempatanKetua($filter['kodept'] ?? null, $idDesa), $persenDesa[$idDesa]->persen_pengurangan ?? null)
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
            $lokasi->klaster = PenguranganSampah::klaster($lokasi->persen, true);
            $lokasi->kecamatan = $lokasi->kecamatan
                ->each(fn ($r) => $r->klaster = PenguranganSampah::klaster($r->persen, true))
                ->filter(fn ($r) => $cocok($r->klaster))->values();

            return $lokasi;
        })->filter(fn ($lokasi) => $lokasi->kecamatan->isNotEmpty())->values();

        $data['kelurahan'] = $data['kelurahan']
            ->each(fn ($r) => $r->klaster = PenguranganSampah::klaster($r->persen, true))
            ->filter(fn ($r) => $cocok($r->klaster))->values();

        $data['kelompok'] = $data['kelompok']->each(function ($r) use ($persenPt, $bulan) {
            // Detail hanya capaian bulan terpilih; ketua tanpa isian tampil baris kosong (detailPublik)
            $r->capaian = $r->capaian->filter(fn ($c) => $bulan && str_starts_with((string) $c->bulan, $bulan))->values();
            $r->persen_pt = $persenPt[$r->kodept]->persen_pengurangan ?? null;
            $r->klaster_pt = PenguranganSampah::klaster($r->persen_pt, true);
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
     * Export "Capaian Program" (admin): lokasi -> kecamatan (dari drilldownPublik) + SEMUA kelurahan berdata
     * per lokasi|kecamatan. Ikut filter bulan & klaster, ambang strict seperti halaman publik.
     */
    public function capaianProgram(array $filter): array
    {
        $data = $this->drilldownPublik(['bulan' => $filter['bulan'] ?? null, 'klaster' => $filter['klaster'] ?? null]);
        $bulan = $data['params']['bulan'];
        $klaster = $data['params']['klaster'];
        $persenDesa = $bulan ? $this->totalPer('desa', ['bulan' => $bulan]) : collect();

        $kelurahan = $this->penempatanData(null)
            ->unique(fn ($r) => $r->location_program.'|'.$r->id_desa)->sortBy('desa')
            ->map(function ($r) use ($persenDesa) {
                $persen = $persenDesa[$r->id_desa]->persen_pengurangan ?? null;

                return (object) ['kunci' => $r->location_program.'|'.$r->id_kecamatan, 'desa' => $r->desa, 'persen' => $persen, 'klaster' => PenguranganSampah::klaster($persen, true)];
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
            'klaster' => PenguranganSampah::klaster($r->persen_pengurangan),
        ])->sortByDesc('persen');
    }

    // Daftar lokasi/kecamatan/kelurahan/PT dari data pendataan (semua bulan); tanpa email pengisi
    private function penempatanData(?string $kodept): Collection
    {
        return $this->penempatanData[(string) $kodept] ??= $this->query(['kodept' => $kodept])
            ->whereNotNull('kc.id_kecamatan')
            ->groupBy('s.location_program', 's.id_desa', 's.kodept', 'd.desa', 'd.baseline', 'd.satuan_baseline', 'd.id_kecamatan', 'kc.kecamatan')
            ->select('s.location_program', 's.id_desa', 'd.desa', 'd.baseline', 'd.satuan_baseline', 'd.id_kecamatan', 'kc.kecamatan', 's.kodept')
            ->get();
    }

    // Kelurahan tiap ketua kelompok (atribut ketua): pilihan lokasi tahun berjalan, fallback penempatan pj_desa
    private function penempatanKetua(?string $kodept, ?string $idDesa = null): Collection
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
            ->when($idDesa, fn (Builder $q, $v) => $q->where('k.id_desa', $v))
            ->select('k.*', 'd.desa', 'd.id_kecamatan', 'kc.kecamatan')
            ->get();
    }

    // PT = PT berdata ∪ PT berketua di kelurahan ini; PT tanpa ketua → ketua/capaian kosong
    private function kelompokDiKelurahan(Collection $pt, Collection $ketua, ?float $persen): Collection
    {
        $first = $pt->first();
        $pt = $pt->whereNotNull('kodept');
        $kodept = $pt->pluck('kodept')->concat($ketua->pluck('kodept'))->unique()->values();
        if ($kodept->isEmpty()) {
            return collect();
        }

        $namaPt = DB::table('ref_satuanpendidikan')->whereIn('npsn', $kodept)->pluck('nm_lemb', 'npsn');
        $mahasiswa = DB::table('mahasiswa as m')
            ->join('mahasiswa_lokasi as ml', 'ml.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->where('ml.tahun', now()->year)->where('ml.id_desa', $first->id_desa)
            ->whereIn('m.kodept', $kodept)
            ->groupBy('m.kodept')
            ->selectRaw('m.kodept, COUNT(*) as jumlah')
            ->pluck('jumlah', 'kodept');
        $dpl = DB::table('dpl')->whereIn('kodept', $kodept)
            ->groupBy('kodept', 'location_program')
            ->selectRaw('kodept, location_program, COUNT(*) as jumlah')
            ->get()->keyBy(fn ($r) => $r->kodept.'|'.$r->location_program);
        // Urut bulan terbaru
        $capaian = $ketua->isEmpty() ? collect() : CapaianKegiatan::whereIn('email', $ketua->pluck('email'))
            ->orderByDesc('bulan')->orderByDesc('created_at')
            ->get();

        // 1 baris per PT di kelurahan ini; detail = gabungan capaian semua ketua PT tsb
        return $kodept->map(function ($kode) use ($pt, $ketua, $first, $namaPt, $mahasiswa, $dpl, $persen, $capaian) {
            $rows = $ketua->where('kodept', $kode);

            return (object) [
                'kodept' => $kode,
                'nama_pt' => $namaPt[$kode] ?? $kode,
                'lokasi' => $first->desa.', '.$first->kecamatan,
                'jumlah_mahasiswa' => (int) ($mahasiswa[$kode] ?? 0),
                'jumlah_dpl' => $pt->where('kodept', $kode)->pluck('location_program')->concat($rows->pluck('location_program'))->unique()
                    ->sum(fn ($lokasi) => (int) ($dpl[$kode.'|'.$lokasi]->jumlah ?? 0)),
                'jumlah_ketua' => $rows->count(),
                'nama_ketua' => $rows->map(fn ($r) => $r->nama_ketua ?: $r->email)->implode(', ') ?: '-',
                // Distinct per email (akses ketua = terdaftar di pj_desa); nama kosong → '-'
                'ketua' => $rows->unique('email')->map(fn ($r) => $r->nama_ketua ?: '-')->sort()->values(),
                // email => nama ketua; dipakai drilldownPublik(), tidak ikut ke data publik
                'ketua_email' => $rows->unique('email')->mapWithKeys(fn ($r) => [$r->email => $r->nama_ketua ?: '-']),
                'persen' => $persen,
                'capaian' => $capaian->whereIn('email', $rows->pluck('email')->all())->values(),
            ];
        })->sortBy('nama_pt')->values();
    }
    // Satu baris per log: bulan (Y-m-01), kelurahan mahasiswa, kunci rumah (kelurahan|bulan|alamat)
    private function query(array $filter): Builder
    {
        $log = $this->logQuery($filter)
            ->selectRaw("p.email, lm.kodept, lm.location_program, ml.id_desa, p.tanggal, DATE_FORMAT(p.tanggal, '%Y-%m-01') as bulan, p.memilah, p.organik_kg, p.anorganik_kg, p.residu_kg")
            ->selectRaw('p.id_pendataan, p.created_at, p.nama_kepala_keluarga, p.alamat_rumah, p.jumlah_anggota_keluarga as jiwa')
            // MD5: kunci pendek agar join/distinct kunci rumah tidak membandingkan string panjang
            ->selectRaw("MD5(CONCAT_WS('|', ml.id_desa, DATE_FORMAT(p.tanggal, '%Y-%m'), {$this->keyPart('p.alamat_rumah')})) as rumah");

        return DB::query()->fromSub($log, 's')
            ->join('desa as d', 'd.id_desa', '=', 's.id_desa')
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->when($filter['id_kecamatan'] ?? null, fn (Builder $q, $v) => $q->where('d.id_kecamatan', $v))
            ->when($filter['id_desa'] ?? null, fn (Builder $q, $v) => $q->where('s.id_desa', $v))
            // Hanya log di bulan terakhir masing-masing kelurahan (dalam set terfilter yang sama)
            ->when($filter['bulan_terakhir_desa'] ?? false, function (Builder $q) use ($filter) {
                // Subquery ringan: tanpa kunci rumah; filter desa/kecamatan sudah diterapkan di query luar
                $terakhir = $this->logQuery(array_diff_key($filter, ['bulan' => 0]))
                    ->groupBy('ml.id_desa')
                    ->selectRaw("ml.id_desa, MAX(DATE_FORMAT(p.tanggal, '%Y-%m-01')) as bulan");
                $q->joinSub($terakhir, 'bt', fn ($j) => $j->on('bt.id_desa', '=', 's.id_desa')->on('bt.bulan', '=', 's.bulan'));
            });
    }

    // Log pendataan + kelurahan terakhir mahasiswa, terfilter bulan/tahun/PT/lokasi (tanpa kolom select)
    private function logQuery(array $filter): Builder
    {
        $lokasiTerakhir = DB::table('mahasiswa_lokasi')
            ->whereNotNull('id_desa')
            ->groupBy('id_mahasiswa')
            ->selectRaw('id_mahasiswa, MAX(tahun) as tahun');

        return DB::table('pendataan_pemilahan_sampah as p')
            ->join('mahasiswa as lm', 'lm.email', '=', 'p.email')
            ->joinSub($lokasiTerakhir, 'lt', 'lt.id_mahasiswa', '=', 'lm.id_mahasiswa')
            ->join('mahasiswa_lokasi as ml', fn ($j) => $j->on('ml.id_mahasiswa', '=', 'lt.id_mahasiswa')->on('ml.tahun', '=', 'lt.tahun'))
            ->when($filter['bulan'] ?? null, function (Builder $q, $v) {
                $awal = Carbon::createFromFormat('Y-m-d', $v.'-01')->startOfDay();
                $q->whereBetween('p.tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->when($filter['tahun'] ?? null, fn (Builder $q, $v) => $q->whereBetween('p.tanggal', [$v.'-01-01', $v.'-12-31']))
            // Filter PT/lokasi di query dalam agar derived table tidak memuat semua pendataan
            ->when($filter['kodept'] ?? null, fn (Builder $q, $v) => $q->where('lm.kodept', $v))
            ->when($filter['lokasi'] ?? null, fn (Builder $q, $v) => $q->where('lm.location_program', $v))
            // Daftar PT hasil pilihan klaster; daftar kosong = tidak ada data
            ->when(array_key_exists('kodept_in', $filter), fn (Builder $q) => $q->whereIn('lm.kodept', $filter['kodept_in']));
    }

    // Normalisasi alamat untuk kunci rumah: lowercase alfanumerik saja
    private function keyPart(string $column): string
    {
        if ($this->supportsRegexpReplace()) {
            return "REGEXP_REPLACE(LOWER({$column}), '[^a-z0-9]', '')";
        }

        // Fallback DB tanpa REGEXP_REPLACE: buang spasi & tanda baca umum
        $text = "LOWER({$column})";
        foreach (["' '", "'.'", "','", "'-'", "'/'", "''''", "'\"'", "'('", "')'", "':'", "';'", "'#'", "'_'", "'&'", 'CHAR(9)'] as $char) {
            $text = "REPLACE({$text}, {$char}, '')";
        }

        return $text;
    }

    // REGEXP_REPLACE tersedia di MySQL >= 8.0.4 dan MariaDB >= 10.0.5
    private function supportsRegexpReplace(): bool
    {
        return $this->regexpReplace ??= (function (): bool {
            if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                return false;
            }
            $raw = preg_replace('/^5\.5\.5-/', '', (string) DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION));
            if (! preg_match('/\d+\.\d+\.\d+/', $raw, $m)) {
                return false;
            }

            return version_compare($m[0], stripos($raw, 'mariadb') !== false ? '10.0.5' : '8.0.4', '>=');
        })();
    }

    private function withJumlah(Builder $query): Builder
    {
        return $query->selectRaw('COUNT(DISTINCT s.rumah) as total_jml_rumah')
            ->selectRaw('COUNT(DISTINCT CASE WHEN s.memilah = 1 THEN s.rumah END) as total_jml_rumah_memilah')
            ->selectRaw('COALESCE(SUM(s.organik_kg), 0) as total_organik')
            ->selectRaw('COALESCE(SUM(s.anorganik_kg), 0) as total_anorganik')
            ->selectRaw('COALESCE(SUM(s.residu_kg), 0) as total_residu');
    }

    // Field baris rekap (kolom D–L LLDIKTI); persen null bila pembagi 0
    private function isiPersen(object $row): object
    {
        $row->jml_rumah = (int) $row->total_jml_rumah;
        $row->jml_rumah_memilah = (int) $row->total_jml_rumah_memilah;
        $row->organik = round((float) $row->total_organik, 2);
        $row->anorganik = round((float) $row->total_anorganik, 2);
        $row->residu = round((float) $row->total_residu, 2);
        $row->total_terkelola = round($row->organik + $row->anorganik, 2);
        $row->total_dihasilkan = round($row->total_terkelola + $row->residu, 2);
        $row->persen_ketaatan = PenguranganSampah::persen($row->jml_rumah_memilah, $row->jml_rumah);
        $row->persen_penurunan = PenguranganSampah::persen($row->total_terkelola, $row->total_dihasilkan);

        return $row;
    }

    // Kolom agregat penurunan timbulan; hanya log dengan jiwa > 0 & koefisien > 0
    private function withPenurunan(Builder $query): Builder
    {
        $berjiwa = 's.jiwa > 0 AND lp.koefisien_timbulan_sampah > 0';

        return $query->leftJoin('lokasi_program as lp', 'lp.id', '=', 's.location_program')
            ->selectRaw('COALESCE(SUM(s.jiwa), 0) as total_jiwa')
            ->selectRaw("SUM(CASE WHEN {$berjiwa} THEN ".self::dihasilkanSql('s').' END) as dihasilkan_berjiwa')
            ->selectRaw("SUM(CASE WHEN {$berjiwa} THEN s.jiwa * lp.koefisien_timbulan_sampah END) as potensi_timbulan");
    }

    // Penurunan timbulan dari total grup: 1 - ΣN / Σ(koef × jiwa) (= kolom P rekap); jadi persen capaian
    private function isiPenurunan(object $row): object
    {
        $row->jml_jiwa = (int) $row->total_jiwa;
        $row->persen_penurunan_timbulan = self::penurunanTimbulan((float) $row->dihasilkan_berjiwa, (float) $row->potensi_timbulan);
        $row->persen_pengurangan = $row->persen_penurunan_timbulan;

        return $row;
    }
}
