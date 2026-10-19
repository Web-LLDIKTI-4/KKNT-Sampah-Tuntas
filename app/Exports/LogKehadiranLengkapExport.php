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
 * Kehadiran seluruh mahasiswa dalam cakupan role: admin/kepala/pemda semua, pt mahasiswa PT-nya, dpl bimbingan.
 */
class LogKehadiranLengkapExport
{
    public function __construct(private User $user, private ?string $bulan = null) {}

    public function query(): Builder
    {
        return DB::table('kehadiran as k')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'k.email')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();
                $q->whereBetween('k.tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->orderBy('k.tanggal')->orderBy('m.nama')->orderBy('k.id_kehadiran')
            // Joined PKs keep lazy() paging stable when legacy joins duplicate a row
            ->orderBy('m.id_mahasiswa')->orderBy('sp.id_sp')
            ->select('k.email', 'k.tanggal', 'k.status_kehadiran', 'k.waktu_masuk', 'k.waktu_pulang', 'm.nama', 'm.nim', 'm.phone', 'sp.nm_lemb');
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
        $time = fn (?string $value) => $value ? Carbon::parse($value)->format('H:i:s') : null;

        return (new XlsxStreamer('Log Kehadiran Mahasiswa', [
            XlsxStreamer::column('Email Address', 'email', 28),
            XlsxStreamer::column('Nama Mahasiswa', 'nama', 28),
            XlsxStreamer::column('Nomor Kontak', 'phone', 16),
            XlsxStreamer::column('NIM', 'nim', 16),
            XlsxStreamer::column('Nama Perguruan Tinggi', 'nm_lemb', 30),
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Status Kehadiran', fn ($row) => ucfirst((string) $row->status_kehadiran), 16),
            XlsxStreamer::column('Jam Masuk', fn ($row) => $time($row->waktu_masuk), 10),
            XlsxStreamer::column('Jam Pulang', fn ($row) => $time($row->waktu_pulang), 10),
        ]))->info(['Bulan' => $this->bulan ? Carbon::createFromFormat('!Y-m', $this->bulan)->translatedFormat('F Y') : 'Semua']);
    }

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'admin', 'kepala', 'pemda' => null,
            'pt', 'dpl' => $query->whereIn('k.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
