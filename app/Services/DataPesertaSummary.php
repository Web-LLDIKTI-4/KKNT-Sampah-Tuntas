<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataPesertaSummary
{
    /**
     * Daftar PT beserta jumlah mahasiswa dan DPL.
     */
    public function all(): Collection
    {
        // Same definitions as header stats in LoginController::statistikPublik()
        $jumlahMahasiswaPerPt = DB::table('mahasiswa')
            ->whereNotNull('kodept')
            ->select('kodept', DB::raw('COUNT(*) as total'))
            ->groupBy('kodept')
            ->pluck('total', 'kodept');

        $jumlahDplPerPt = DB::table('dpl')
            ->whereNotNull('kodept')
            ->select('kodept', DB::raw('COUNT(*) as total'))
            ->groupBy('kodept')
            ->pluck('total', 'kodept');

        $kodept = $jumlahMahasiswaPerPt->keys()->merge($jumlahDplPerPt->keys())->unique()->values();
        $namaPt = DB::table('ref_satuanpendidikan')->whereIn('npsn', $kodept)->pluck('nm_lemb', 'npsn');

        return $kodept->map(fn ($kode) => (object) [
            'kodept' => (string) $kode,
            'nama_pt' => $namaPt[$kode] ?? (string) $kode,
            'jumlah_mahasiswa' => (int) ($jumlahMahasiswaPerPt[$kode] ?? 0),
            'jumlah_dpl' => (int) ($jumlahDplPerPt[$kode] ?? 0),
        ])->sortBy('nama_pt', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
}
