<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sebaran mahasiswa per desa untuk peta publik (angka kecil disamarkan).
 */
class PetaSebaranService
{
    public const FILTER_CACHE_KEY = 'login.peta.filter.v4';

    public const VERSION_CACHE_KEY = 'login.peta.ver';

    public const MASK_BELOW = 3;

    public function __construct(private PenguranganSampahService $sampah)
    {
    }

    // Invalidate all cached peta data by bumping the version used in data keys.
    public static function flushCache(): void
    {
        Cache::forever(self::VERSION_CACHE_KEY, (int) Cache::get(self::VERSION_CACHE_KEY, 1) + 1);
        Cache::forget(self::FILTER_CACHE_KEY);
    }

    /**
     * @return array{tahun: array<int, int>, tahun_default: int, pt: array<int, array{kodept: string, nama: string}>}
     */
    public function options(): array
    {
        $current = (int) date('Y');

        $tahun = DB::table('mahasiswa_lokasi')->whereNotNull('tahun')->distinct()->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->push($current)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        // Semua PT (tanpa cek kehadiran, agar tidak jadi oracle)
        $pt = DB::table('ref_satuanpendidikan as sp')
            ->orderBy('sp.nm_lemb')
            ->orderBy('sp.npsn')
            ->get(['sp.npsn', 'sp.nm_lemb'])
            ->map(fn ($row) => ['kodept' => (string) $row->npsn, 'nama' => (string) $row->nm_lemb])
            ->all();

        return ['tahun' => $tahun, 'tahun_default' => $current, 'pt' => $pt];
    }

    /**
     * @param  array{tahun: int, kodept: ?string}  $filter
     */
    public function sebaran(array $filter): array
    {
        $kodept = $filter['kodept'] ?? null;
        $modePt = $kodept !== null;

        // Filter tahun/PT di JOIN agar desa tanpa mahasiswa tetap muncul (0)
        $query = DB::table('desa as d')
            ->leftJoin('kecamatan as k', 'k.id_kecamatan', '=', 'd.id_kecamatan')
            ->leftJoin('mahasiswa_lokasi as ml', function ($join) use ($filter) {
                $join->on('ml.id_desa', '=', 'd.id_desa')->where('ml.tahun', '=', $filter['tahun']);
            });

        if ($modePt) {
            $query->leftJoin('mahasiswa as m', function ($join) use ($kodept) {
                $join->on('m.id_mahasiswa', '=', 'ml.id_mahasiswa')->where('m.kodept', '=', $kodept);
            })->havingRaw('COUNT(DISTINCT m.id_mahasiswa) > 0');
        }

        $rows = $query
            ->groupBy('d.id_desa', 'd.desa', 'd.latitude', 'd.longitude', 'd.id_kecamatan', 'k.kecamatan')
            ->orderBy('d.desa')
            ->get([
                'd.id_desa', 'd.desa', 'd.latitude', 'd.longitude', 'd.id_kecamatan', 'k.kecamatan',
                DB::raw('COUNT(DISTINCT '.($modePt ? 'm' : 'ml').'.id_mahasiswa) as jumlah'),
            ]);

        // Periode = bulan terakhir global (label); persen memakai bulan terakhir masing-masing desa
        $periode = $this->sampah->bulanTerakhir($kodept, $filter['tahun']);
        $persenDesa = $persenKecamatan = collect();
        if ($periode !== null) {
            // Kecamatan = agregat log bulan terakhir tiap desanya (konsisten dengan persen desa)
            $sampahFilter = ['tahun' => $filter['tahun'], 'kodept' => $kodept, 'bulan_terakhir_desa' => true];
            ['desa' => $persenDesa, 'kecamatan' => $persenKecamatan] = $this->sampah->totalDesaKecamatan($sampahFilter);
        }

        $total = 0;
        $masked = 0;
        $desaAktif = 0;
        $kecamatanAktif = [];
        $desa = [];
        $kecamatan = [];
        $belumDipetakan = [];

        foreach ($rows as $row) {
            $count = (int) $row->jumlah;

            // Tanpa koordinat: tidak masuk peta/summary, dicatat bila punya mahasiswa atau capaian
            if ($row->latitude === null || $row->longitude === null) {
                if ($count > 0 || $persenDesa->has((string) $row->id_desa)) {
                    $belumDipetakan[] = ['desa' => (string) $row->desa, 'kecamatan' => (string) $row->kecamatan];
                }

                continue;
            }

            if ($count > 0) {
                $desaAktif++;
                if ($row->id_kecamatan !== null) {
                    $kecamatanAktif[(string) $row->id_kecamatan] = true;
                }
            }

            $item = [
                'desa' => (string) $row->desa,
                'kecamatan' => (string) $row->kecamatan,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
            ];

            $persen = $this->persen($persenDesa->get((string) $row->id_desa));

            if ($row->id_kecamatan !== null) {
                $kecamatan[(string) $row->id_kecamatan] ??= [
                    'kecamatan' => (string) $row->kecamatan,
                    'persen_pengurangan' => $this->persen($persenKecamatan->get((string) $row->id_kecamatan)),
                ];
            }

            $isMasked = $count > 0 && $count < self::MASK_BELOW;
            $isMasked ? $masked++ : $total += $count;

            $desa[] = $item + [
                'jumlah_mahasiswa' => $isMasked ? null : $count,
                'jumlah_label' => $isMasked ? '<'.self::MASK_BELOW : (string) $count,
                'tier' => $this->tier($count),
                'persen_pengurangan' => $persen,
            ];
        }

        return [
            'filter' => $filter,
            'mode' => $modePt ? 'pt' : 'all',
            'periode' => $periode === null ? null : [
                'bulan' => $periode,
                'label' => Carbon::parse($periode.'-01')->locale('id')->translatedFormat('M Y'),
            ],
            'summary' => [
                'total_mahasiswa' => $total,
                'total_label' => $total.($masked > 0 ? '+' : ''),
                'jumlah_desa' => $desaAktif,
                'jumlah_kecamatan' => count($kecamatanAktif),
                'desa_tersamar' => $masked,
                'belum_dipetakan' => $belumDipetakan,
                'jumlah_belum_dipetakan' => count($belumDipetakan),
            ],
            'desa' => $desa,
            // Nama kecamatan A-Z (case-insensitive)
            'kecamatan' => collect($kecamatan)
                ->sortBy('kecamatan', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        ];
    }

    private function persen(?object $row): ?float
    {
        return $row?->persen_pengurangan === null ? null : round((float) $row->persen_pengurangan, 2);
    }

    private function tier(int $count): int
    {
        return match (true) {
            $count === 0 => 0,
            $count <= 5 => 1,
            $count <= 10 => 2,
            $count <= 20 => 3,
            $count <= 30 => 4,
            default => 5,
        };
    }
}
