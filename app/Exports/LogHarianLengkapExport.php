<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Log harian lengkap, di-scope per role: admin/kepala/pemda semua, pt mahasiswa PT-nya,
 * dpl mahasiswa bimbingan, mahasiswa datanya sendiri.
 */
class LogHarianLengkapExport
{
    // Mahasiswa: kolom identitas & lokasi disembunyikan, sama dengan tabel Log Harian
    private bool $modeMahasiswa;

    public function __construct(private User $user, private ?string $bulan = null)
    {
        $this->modeMahasiswa = $user->role === 'mahasiswa';
    }

    public function query(): Builder
    {
        $lokasiTerakhir = DB::table('mahasiswa_lokasi')
            ->whereNotNull('id_desa')
            ->groupBy('id_mahasiswa')
            ->selectRaw('id_mahasiswa, MAX(tahun) as tahun');

        return DB::table('logkegiatan as l')
            ->whereNotNull('l.deskripsi')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'l.email')
            ->leftJoin('kategori_kegiatan as k', 'k.id_kategori', '=', 'l.id_kategori')
            ->leftJoinSub($lokasiTerakhir, 'lt', 'lt.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->leftJoin('mahasiswa_lokasi as ml', fn ($j) => $j->on('ml.id_mahasiswa', '=', 'lt.id_mahasiswa')->on('ml.tahun', '=', 'lt.tahun'))
            ->leftJoin('desa as d', 'd.id_desa', '=', 'ml.id_desa')
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'm.location_program')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();
                $q->whereBetween('l.tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->orderBy('l.tanggal')->orderBy('l.created_at')->orderBy('l.id_log')
            // Joined PKs keep lazy() paging stable when legacy joins duplicate a log row
            ->orderBy('m.id_mahasiswa')->orderBy('ml.id_lokasi')
            ->select('l.*', 'm.nama', 'm.phone', 'lp.nama_lokasi', 'kc.kecamatan', 'd.desa', 'k.nama_kategori');
    }

    public function download(string $filename): StreamedResponse
    {
        return $this->streamer()->download($filename, $this->query()->lazy(1000));
    }

    public function writeTo(string $path): void
    {
        $this->streamer()->writeTo($path, $this->query()->lazy(1000));
    }

    private function streamer(): XlsxStreamer
    {
        return (new XlsxStreamer('Log Aktivitas Harian Mahasiswa', $this->columns()))
            ->info(['Bulan' => $this->bulan ? Carbon::createFromFormat('!Y-m', $this->bulan)->translatedFormat('F Y') : 'Semua']);
    }

    private function columns(): array
    {
        $kegiatan = [
            XlsxStreamer::column('Kategori Kegiatan', 'nama_kategori', 24),
            XlsxStreamer::column('Nama Aktivitas', 'nama_aktivitas', 30),
            XlsxStreamer::column('Deskripsi Kegiatan', fn ($row) => strip_tags((string) $row->deskripsi), 45),
            XlsxStreamer::column('Volume/Kuantitas Output', 'volume', 14, 'number'),
            XlsxStreamer::column('Satuan', 'satuan', 18),
            XlsxStreamer::column('Tautan Bukti', 'tautan', 35),
        ];

        if ($this->modeMahasiswa) {
            return [XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'), ...$kegiatan];
        }

        return [
            XlsxStreamer::column('Timestamp', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y H:i:s') : null, 20),
            XlsxStreamer::column('Email Address', 'email', 28),
            XlsxStreamer::column('Nama Mahasiswa Penginput Data', 'nama', 28),
            XlsxStreamer::column('Nomor Kontak', 'phone', 16),
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Kabupaten/Kota', 'nama_lokasi', 20),
            XlsxStreamer::column('Nama Kecamatan', 'kecamatan', 20),
            XlsxStreamer::column('Nama Kelurahan/Desa', 'desa', 22),
            ...$kegiatan,
        ];
    }

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'admin', 'kepala', 'pemda' => null,
            'mahasiswa' => $query->where('l.email', $this->user->email),
            'pt', 'dpl' => $query->whereIn('l.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
