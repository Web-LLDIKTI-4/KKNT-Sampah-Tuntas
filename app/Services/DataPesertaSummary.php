<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
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

        // PT accounts store kode PT (npsn) in users.email
        $jumlahPicPerPt = DB::table('users')
            ->where('role', 'pt')
            ->select('email', DB::raw('COUNT(*) as total'))
            ->groupBy('email')
            ->pluck('total', 'email');

        $jumlahKetuaPerPt = DB::table('mahasiswa')
            ->join('pj_desa', 'pj_desa.email', '=', 'mahasiswa.email')
            ->whereNotNull('mahasiswa.kodept')
            ->select('mahasiswa.kodept', DB::raw('COUNT(DISTINCT mahasiswa.email) as total'))
            ->groupBy('mahasiswa.kodept')
            ->pluck('total', 'kodept');

        $kodept = $this->kodeptBase()->pluck('kodept');
        $namaPt = DB::table('ref_satuanpendidikan')->whereIn('npsn', $kodept)->pluck('nm_lemb', 'npsn');

        return $kodept->map(fn ($kode) => (object) [
            'kodept' => (string) $kode,
            'nama_pt' => $namaPt[$kode] ?? (string) $kode,
            'jumlah_mahasiswa' => (int) ($jumlahMahasiswaPerPt[$kode] ?? 0),
            'jumlah_dpl' => (int) ($jumlahDplPerPt[$kode] ?? 0),
            'jumlah_pic' => (int) ($jumlahPicPerPt[$kode] ?? 0),
            'jumlah_ketua_kelompok' => (int) ($jumlahKetuaPerPt[$kode] ?? 0),
        ])->sortBy('nama_pt', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    // Population of mobilisasi status & list: every pj_desa row
    public function mobilisasiBase(): Builder
    {
        return DB::table('pj_desa');
    }

    // Same UNION as header jumlahPt in LoginController::statistikPublik()
    public function kodeptBase(): Builder
    {
        return DB::table('mahasiswa')->whereNotNull('kodept')->select('kodept')
            ->union(DB::table('dpl')->whereNotNull('kodept')->select('kodept'));
    }

    /**
     * Satu PT yang ada di all() (punya mahasiswa/dpl), tanpa hitung ulang seluruh daftar.
     */
    public function find(string $kodept): ?object
    {
        $ada = DB::table('mahasiswa')->where('kodept', $kodept)->exists()
            || DB::table('dpl')->where('kodept', $kodept)->exists();
        if (! $ada) {
            return null;
        }

        return (object) [
            'kodept' => $kodept,
            'nama_pt' => (string) (DB::table('ref_satuanpendidikan')->where('npsn', $kodept)->value('nm_lemb') ?? $kodept),
        ];
    }

    /**
     * Ringkasan mobilisasi (semua baris pj_desa) & pelaksanaan (PT peserta di $kodept).
     *
     * @return array{mobilisasi: array{sudah: int, belum: int, total: int}, pelaksanaan: array{sudah: int, belum: int, total: int}}
     */
    public function status(Collection $kodept): array
    {
        $mobilisasi = $this->mobilisasiBase()
            ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN status_mobilisasi = 'sudah' THEN 1 ELSE 0 END), 0) as sudah")
            ->first();

        $kodept = $kodept->map(fn ($kode) => (string) $kode)->unique()->values();
        $sudahPelaksanaan = $kodept->isEmpty() ? 0 : DB::table('pelaksanaan_kkn')->whereIn('kodept', $kodept)->count();

        return [
            'mobilisasi' => [
                'sudah' => (int) $mobilisasi->sudah,
                'belum' => (int) $mobilisasi->total - (int) $mobilisasi->sudah,
                'total' => (int) $mobilisasi->total,
            ],
            'pelaksanaan' => [
                'sudah' => $sudahPelaksanaan,
                'belum' => $kodept->count() - $sudahPelaksanaan,
                'total' => $kodept->count(),
            ],
        ];
    }

    /**
     * Daftar anggota satu PT per jenis; filter wajib sama dengan hitungan di all().
     *
     * @return array<int, array{nim: string, nama: string}>|array<int, array{kode_pt: string, nama_pt: string, nama_user: string}>
     */
    public function members(string $kodept, string $jenis): array
    {
        if ($jenis === 'pic') {
            // PT account: users.email holds kode PT
            $namaPt = DB::table('ref_satuanpendidikan')->where('npsn', $kodept)->value('nm_lemb');

            return DB::table('users')->where('role', 'pt')->where('email', $kodept)
                ->orderBy('name')->get(['email', 'name'])
                ->map(fn ($row) => [
                    'kode_pt' => (string) ($row->email ?? '-'),
                    'nama_pt' => (string) ($namaPt ?? '-'),
                    'nama_user' => (string) ($row->name ?? '-'),
                ])->all();
        }

        $query = match ($jenis) {
            'mahasiswa' => DB::table('mahasiswa')->where('kodept', $kodept)->select('nama', 'nim'),
            'dpl' => DB::table('dpl')->where('kodept', $kodept)->select('nama', 'nidn as nim'),
            // One whole row (MAX id) per distinct email, matching COUNT(DISTINCT mahasiswa.email)
            'ketua' => DB::table('mahasiswa')
                ->where('mahasiswa.kodept', $kodept)
                ->whereExists(fn ($q) => $q->from('pj_desa')->whereColumn('pj_desa.email', 'mahasiswa.email'))
                ->whereRaw('mahasiswa.id_mahasiswa = (select max(m2.id_mahasiswa) from mahasiswa m2 where m2.email = mahasiswa.email and m2.kodept = mahasiswa.kodept)')
                ->select('mahasiswa.nama', 'mahasiswa.nim'),
        };

        return $query->orderBy('nama')->get()->map(fn ($row) => [
            'nim' => (string) ($row->nim ?? '-'),
            'nama' => (string) ($row->nama ?? '-'),
        ])->all();
    }
}
