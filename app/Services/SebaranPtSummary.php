<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SebaranPtSummary
{
    // Ikut versi peta: flush data mahasiswa/dpl ikut menyegarkan cache ini
    public function cached(): Collection
    {
        return Cache::remember('login.sebaran_pt.v1.'.Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1), now()->addMinutes(10), fn () => $this->all());
    }

    /**
     * Pasangan PT–lokasi program aktual (dari mahasiswa + dpl), satu baris per pasangan.
     *
     * @return Collection<int, object{kodept: string, nama_pt: string, nama_lokasi: string}>
     */
    public function all(): Collection
    {
        // Same UNION as jumlah_pt in LokasiProgramSummary::all()
        $pasangan = DB::table('mahasiswa')
            ->whereNotNull('location_program')->whereNotNull('kodept')
            ->select('location_program', 'kodept')
            ->union(DB::table('dpl')->whereNotNull('location_program')->whereNotNull('kodept')->select('location_program', 'kodept'));

        return DB::query()->fromSub($pasangan, 'pasangan')
            ->join('lokasi_program', 'lokasi_program.id', '=', 'pasangan.location_program')
            ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'pasangan.kodept')
            ->get(['pasangan.kodept', 'ref_satuanpendidikan.nm_lemb', 'lokasi_program.nama_lokasi'])
            ->map(fn ($row) => (object) [
                'kodept' => (string) $row->kodept,
                'nama_pt' => (string) ($row->nm_lemb ?? $row->kodept),
                'nama_lokasi' => (string) $row->nama_lokasi,
            ])
            ->sortBy([
                fn ($a, $b) => strnatcasecmp($a->nama_pt, $b->nama_pt),
                fn ($a, $b) => strnatcasecmp($a->nama_lokasi, $b->nama_lokasi),
            ])
            ->values();
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
            ->map(fn (Collection $rows) => $rows->pluck('nama_lokasi')->all())
            ->all();
    }
}
