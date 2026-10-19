<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\HtmlSanitizer;
use App\Support\XlsxStreamer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Log bulanan seluruh mahasiswa dalam cakupan role: admin/kepala/pemda semua, pt mahasiswa PT-nya, dpl bimbingan.
 */
class LogBulananLengkapExport
{
    public function __construct(private User $user, private ?string $bulan = null) {}

    public function query(): Builder
    {
        return DB::table('logkegiatan_bulanan as lb')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'lb.email')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                [$tahun, $bln] = explode('-', $bulan);
                $q->where('lb.tahun', (int) $tahun)->where('lb.bulan', (int) $bln);
            })
            ->orderBy('lb.tahun')->orderBy('lb.bulan')->orderBy('m.nama')->orderBy('lb.id_logbulanan')
            // Joined PKs keep lazy() paging stable when legacy joins duplicate a row
            ->orderBy('m.id_mahasiswa')->orderBy('sp.id_sp')
            ->select('lb.*', 'm.nama', 'm.nim', 'm.phone', 'sp.nm_lemb');
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
        return (new XlsxStreamer('Log Bulanan Mahasiswa', [
            XlsxStreamer::column('Timestamp', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y H:i:s') : null, 20),
            XlsxStreamer::column('Email Address', 'email', 28),
            XlsxStreamer::column('Nama Mahasiswa', 'nama', 28),
            XlsxStreamer::column('Nomor Kontak', 'phone', 16),
            XlsxStreamer::column('NIM', 'nim', 16),
            XlsxStreamer::column('Nama Perguruan Tinggi', 'nm_lemb', 30),
            XlsxStreamer::column('Bulan', fn ($row) => $row->tahun && $row->bulan ? Carbon::create((int) $row->tahun, (int) $row->bulan)->translatedFormat('F Y') : $row->bulan, 16),
            XlsxStreamer::column('Deskripsi', fn ($row) => HtmlSanitizer::plainText($row->deskripsi), 70),
            XlsxStreamer::column('Nilai', 'nilai', 8, 'number'),
        ]))->info(['Bulan' => $this->bulan ? Carbon::createFromFormat('!Y-m', $this->bulan)->translatedFormat('F Y') : 'Semua']);
    }

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'admin', 'kepala', 'pemda' => null,
            'pt', 'dpl' => $query->whereIn('lb.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
