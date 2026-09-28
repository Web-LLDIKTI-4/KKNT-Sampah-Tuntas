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
        $kodeptDpl = DB::table('users')
            ->join('dpl', 'dpl.email', '=', 'users.email')
            ->whereNotNull('users.location_program')
            ->whereNotNull('dpl.kodept')
            ->select('users.location_program', 'dpl.kodept');

        $kodeptMahasiswa = DB::table('users')
            ->join('mahasiswa', 'mahasiswa.email', '=', 'users.email')
            ->whereNotNull('users.location_program')
            ->whereNotNull('mahasiswa.kodept')
            ->select('users.location_program', 'mahasiswa.kodept');

        $jumlahPtPerLokasi = DB::query()
            ->fromSub($kodeptDpl->unionAll($kodeptMahasiswa), 'gabungan')
            ->select('location_program', DB::raw('COUNT(DISTINCT kodept) as total'))
            ->groupBy('location_program')
            ->pluck('total', 'location_program');

        return LokasiProgram::query()
            ->withCount([
                'users as jumlah_dpl' => fn ($q) => $q->where('role', 'dpl'),
                'users as jumlah_mahasiswa' => fn ($q) => $q->where('role', 'mahasiswa'),
            ])
            ->orderBy('nama_lokasi')
            ->get()
            ->each(fn ($lokasi) => $lokasi->jumlah_pt = $jumlahPtPerLokasi[$lokasi->id] ?? 0);
    }
}
