<?php

namespace App\Exports;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Support\HtmlSanitizer;
use App\Support\XlsxStreamer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogbulanandplExport
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(private string $email) {}

    public function download(string $filename): StreamedResponse
    {
        // Every row belongs to the same DPL: load it once
        $dpl = Dpl::with('sp:npsn,nm_lemb')->where('email', $this->email)->first();
        $rows = Dpllaporan::where('email', $this->email)
            ->orderBy('created_at', 'desc')
            ->orderBy('id_laporan')
            ->lazy(1000);

        return (new XlsxStreamer('Laporan Bulanan DPL', [
            XlsxStreamer::column('Nama DPL', fn () => $dpl?->nama, 30),
            XlsxStreamer::column('NIDN', fn () => $dpl?->nidn, 15),
            XlsxStreamer::column('Perguruan Tinggi', fn () => $dpl?->sp?->nm_lemb, 35),
            XlsxStreamer::column('Tahun', 'tahun', 8),
            XlsxStreamer::column('Bulan', fn (Dpllaporan $row) => self::MONTHS[(int) $row->bulan] ?? null, 12),
            XlsxStreamer::column('Deskripsi', fn (Dpllaporan $row) => HtmlSanitizer::plainText($row->deskripsi), 70),
        ]))
            ->info(['DPL' => $dpl?->nama, 'Email' => $this->email])
            ->download($filename, $rows);
    }
}
