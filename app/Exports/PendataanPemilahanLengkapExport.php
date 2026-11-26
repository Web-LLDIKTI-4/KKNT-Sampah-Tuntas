<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\UnbufferedCursor;
use App\Support\XlsxStreamer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pendataan sampah penduduk seluruh mahasiswa dalam cakupan role: pt mahasiswa PT-nya, dpl bimbingan.
 */
class PendataanPemilahanLengkapExport
{
    public function __construct(private User $user, private ?string $bulan = null) {}

    public function query(): Builder
    {
        $lokasiTerakhir = DB::table('mahasiswa_lokasi')
            ->whereNotNull('id_desa')
            ->groupBy('id_mahasiswa')
            ->selectRaw('id_mahasiswa, MAX(tahun) as tahun');

        return DB::table('pendataan_pemilahan_sampah as p')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'p.email')
            ->leftJoinSub($lokasiTerakhir, 'lt', 'lt.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->leftJoin('mahasiswa_lokasi as ml', fn ($j) => $j->on('ml.id_mahasiswa', '=', 'lt.id_mahasiswa')->on('ml.tahun', '=', 'lt.tahun'))
            ->leftJoin('desa as d', 'd.id_desa', '=', 'ml.id_desa')
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'm.location_program')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();
                $q->whereBetween('p.tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->orderBy('p.tanggal')->orderBy('p.created_at')->orderBy('p.id_pendataan')
            // Joined PKs keep the order deterministic when legacy joins duplicate a row
            ->orderBy('m.id_mahasiswa')->orderBy('ml.id_lokasi')
            ->select('p.email', 'p.tanggal', 'p.nama_kepala_keluarga', 'p.jumlah_anggota_keluarga', 'p.alamat_rumah', 'p.rt', 'p.rw', 'p.memilah',
                'p.organik_kg', 'p.anorganik_kg', 'p.residu_kg', 'm.nama', 'm.nim', 'lp.nama_lokasi', 'kc.kecamatan', 'd.desa');
    }

    public function download(string $filename): StreamedResponse
    {
        return $this->streamer()->download($filename, UnbufferedCursor::each($this->query()));
    }

    public function writeTo(string $path): void
    {
        $this->streamer()->writeTo($path, UnbufferedCursor::each($this->query()));
    }

    private function streamer(): XlsxStreamer
    {
        return (new XlsxStreamer('Pendataan Sampah Penduduk', [
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Email Address', 'email', 28),
            XlsxStreamer::column('Nama Mahasiswa', 'nama', 28),
            XlsxStreamer::column('NIM', 'nim', 16),
            XlsxStreamer::column('Kabupaten/Kota', 'nama_lokasi', 20),
            XlsxStreamer::column('Nama Kecamatan', 'kecamatan', 20),
            XlsxStreamer::column('Nama Kelurahan/Desa', 'desa', 22),
            XlsxStreamer::column('Nama Kepala Keluarga', 'nama_kepala_keluarga', 28),
            XlsxStreamer::column('Anggota Keluarga', 'jumlah_anggota_keluarga', 12, 'number'),
            XlsxStreamer::column('Alamat Rumah', 'alamat_rumah', 40),
            XlsxStreamer::column('RT', 'rt', 6),
            XlsxStreamer::column('RW', 'rw', 6),
            XlsxStreamer::column('Memilah', fn ($row) => $row->memilah ? 'Ya' : 'Tidak', 10),
            XlsxStreamer::column('Organik (kg)', fn ($row) => (float) $row->organik_kg, 12),
            XlsxStreamer::column('Anorganik (kg)', fn ($row) => (float) $row->anorganik_kg, 12),
            XlsxStreamer::column('Residu (kg)', fn ($row) => (float) $row->residu_kg, 12),
        ]))->info(['Bulan' => $this->bulan ? Carbon::createFromFormat('!Y-m', $this->bulan)->translatedFormat('F Y') : 'Semua']);
    }

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'pt', 'dpl' => $query->whereIn('p.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
