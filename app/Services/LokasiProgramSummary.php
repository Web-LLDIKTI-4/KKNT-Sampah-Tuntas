<?php

namespace App\Services;

use App\Models\LokasiProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LokasiProgramSummary
{
    /**
     * Daftar lokasi program beserta jumlah DPL, mahasiswa, dan PT.
     */
    public function all(): Collection
    {
        // Count from master tables (not users) so it matches header stats in LoginController::statistikPublik()
        $jumlahDplPerLokasi = DB::table('dpl')
            ->whereNotNull('location_program')
            ->select('location_program', DB::raw('COUNT(*) as total'))
            ->groupBy('location_program')
            ->pluck('total', 'location_program');

        $jumlahMahasiswaPerLokasi = DB::table('mahasiswa')
            ->whereNotNull('location_program')
            ->select('location_program', DB::raw('COUNT(*) as total'))
            ->groupBy('location_program')
            ->pluck('total', 'location_program');

        // PT from mahasiswa + dpl; UNION dedupes (lokasi, kodept) pairs
        $ptPerLokasi = DB::table('mahasiswa')
            ->whereNotNull('location_program')->whereNotNull('kodept')
            ->select('location_program', 'kodept')
            ->union(DB::table('dpl')->whereNotNull('location_program')->whereNotNull('kodept')->select('location_program', 'kodept'));

        $jumlahPtPerLokasi = DB::query()->fromSub($ptPerLokasi, 'pt')
            ->select('location_program', DB::raw('COUNT(*) as total'))
            ->groupBy('location_program')
            ->pluck('total', 'location_program');

        return LokasiProgram::query()
            ->orderBy('nama_lokasi')
            ->get()
            ->each(function ($lokasi) use ($jumlahDplPerLokasi, $jumlahMahasiswaPerLokasi, $jumlahPtPerLokasi) {
                $lokasi->jumlah_dpl = $jumlahDplPerLokasi[$lokasi->id] ?? 0;
                $lokasi->jumlah_mahasiswa = $jumlahMahasiswaPerLokasi[$lokasi->id] ?? 0;
                $lokasi->jumlah_pt = $jumlahPtPerLokasi[$lokasi->id] ?? 0;
            });
    }
}
