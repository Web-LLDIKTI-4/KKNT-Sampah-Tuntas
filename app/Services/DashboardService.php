<?php

namespace App\Services;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Kehadiran;
use App\Models\Kpicapaian;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Angka ringkasan dashboard per role.
 */
class DashboardService
{
    public function __construct(private KpiRekapService $rekap) {}

    public function forMahasiswa(User $user): array
    {
        $email = $user->email;

        return [
            'jumlahmahasiswa' => Mahasiswa::count(),
            'jumlahcapaiankpi' => $user->akses === 'pjdesa'
                ? Kpicapaian::where('email', $email)->distinct()->count('id_kpi')
                : 0,
            'jumlahlogbulanan' => Logbulanan::where('email', $email)->distinct()->count('bulan'),
            'jumlahlogkegiatan' => Logkegiatan::where('email', $email)->whereNotNull('deskripsi')->distinct()->count('tanggal'),
            'kehadiran' => Kehadiran::where('email', $email)->whereDate('tanggal', today())->first(),
        ] + $this->common();
    }

    public function forDpl(User $user): array
    {
        $mentees = Dplmentoring::ofDpl($user)->select('email_mahasiswa');

        return [
            'jumlahlaporandpl' => Dpllaporan::where('email', $user->email)->distinct()->count('bulan'),
            'jumlahdplmentoring' => Dplmentoring::ofDpl($user)->count(),
            'jumlahmahasiswa' => Dplmentoring::ofDpl($user)->count(),
            'jumlahdplnilaikonversi' => Nilaikonversi::where('email_dpl', $user->email)
                ->whereIn('id_mahasiswa', Mahasiswa::whereIn('email', $mentees)->select('id_mahasiswa'))
                ->distinct()->count('id_mahasiswa'),
            'jumlahlogbulanan' => $this->distinctPairs('logkegiatan_bulanan', 'bulan', $mentees),
            'jumlahlogkegiatan' => $this->distinctPairs('logkegiatan', 'tanggal', $mentees, 'deskripsi'),
            'jumlahdpl' => 0,
        ] + $this->common();
    }

    public function forPt(User $user): array
    {
        $emails = Mahasiswa::visibleTo($user)->select('email');

        return [
            'jumlahlaporandpl' => Dpllaporan::whereIn('email', Dpl::where('kodept', $user->email)->select('email'))->count(),
            'jumlahmahasiswa' => Mahasiswa::visibleTo($user)->count(),
            'jumlahlogbulanan' => $this->distinctPairs('logkegiatan_bulanan', 'bulan', $emails),
            'jumlahlogkegiatan' => $this->distinctPairs('logkegiatan', 'tanggal', $emails, 'deskripsi'),
            'jumlahdpl' => Dpl::where('kodept', $user->email)->count(),
            'kpiHome' => $this->kpiHome(['kodept' => $user->email], false),
        ] + $this->common();
    }

    // Tampilan sama dengan PT, tetapi angka ditotal untuk semua PT
    public function forKepala(): array
    {
        return [
            'jumlahlaporandpl' => Dpllaporan::count(),
            'jumlahmahasiswa' => Mahasiswa::count(),
            'jumlahlogbulanan' => $this->distinctPairs('logkegiatan_bulanan', 'bulan'),
            'jumlahlogkegiatan' => $this->distinctPairs('logkegiatan', 'tanggal', null, 'deskripsi'),
            'kpiHome' => $this->kpiHome([], true),
        ] + $this->common();
    }

    private function kpiHome(array $filter, bool $perPt): array
    {
        // Laporan capaian KPI (pengurangan sampah) dimuat terpisah lewat AJAX dari Dashboard KPI
        return [
            'perPt' => $perPt,
            'lokasi' => $this->rekap->lokasiTable($filter),
        ];
    }

    public function forAdmin(): array
    {
        return [
            'jumlahlaporandpl' => Dpllaporan::count(),
            'jumlahmahasiswa' => Mahasiswa::count(),
            'jumlahlogbulanan' => $this->distinctPairs('logkegiatan_bulanan', 'bulan'),
            'jumlahlogkegiatan' => $this->distinctPairs('logkegiatan', 'tanggal', null, 'deskripsi'),
            'jumlahdplmentoring' => Dplmentoring::count(),
            'jumlahdplnilaikonversi' => Nilaikonversi::whereIn(
                'id_mahasiswa',
                Mahasiswa::whereIn('email', Dplmentoring::select('email_mahasiswa'))->select('id_mahasiswa')
            )->distinct()->count('id_mahasiswa'),
            'kpiHome' => $this->kpiHome([], true),
        ] + $this->common();
    }

    private function common(): array
    {
        return [
            'jumlahdpl' => Dpl::count(),
            'jumlahpt' => Mahasiswa::whereNotNull('kodept')->distinct()->count('kodept'),
            'saran' => collect(),
        ];
    }

    // Jumlah kombinasi unik (email, kolom), mis. hari aktif per mahasiswa
    private function distinctPairs(string $table, string $column, ?Builder $emails = null, ?string $notNull = null): int
    {
        $query = DB::table($table)->select('email', $column)->distinct();
        if ($notNull) {
            $query->whereNotNull($notNull);
        }
        if ($emails) {
            $query->whereIn('email', $emails->toBase());
        }

        return DB::query()->fromSub($query, 'grouped')->count();
    }
}
