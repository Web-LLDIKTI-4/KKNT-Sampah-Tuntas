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
        // Same definitions as header stats in LoginController::statistikPublik()
        $jumlahDplPerLokasi = DB::table('dpl')
            ->join('users', 'users.email', '=', 'dpl.email')
            ->whereNotNull('users.location_program')
            ->select('users.location_program', DB::raw('COUNT(*) as total'))
            ->groupBy('users.location_program')
            ->pluck('total', 'location_program');

        $jumlahMahasiswaPerLokasi = DB::table('mahasiswa')
            ->join('users', 'users.email', '=', 'mahasiswa.email')
            ->whereNotNull('users.location_program')
            ->select('users.location_program', DB::raw('COUNT(*) as total'))
            ->groupBy('users.location_program')
            ->pluck('total', 'location_program');

        $jumlahPtPerLokasi = DB::table('mahasiswa')
            ->join('users', 'users.email', '=', 'mahasiswa.email')
            ->whereNotNull('users.location_program')
            ->whereNotNull('mahasiswa.kodept')
            ->select('users.location_program', DB::raw('COUNT(DISTINCT mahasiswa.kodept) as total'))
            ->groupBy('users.location_program')
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
