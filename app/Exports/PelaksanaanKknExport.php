<?php

namespace App\Exports;

use App\Support\XlsxStreamer;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Daftar Waktu Pelaksanaan KKN; $rows = query tabel (search/order aktif), $lokasiPerPt = SebaranPtSummary::lokasiPerPt()
class PelaksanaanKknExport
{
    public function __construct(
        private iterable $rows,
        private array $lokasiPerPt,
        private ?string $keyword = null,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        return (new XlsxStreamer('Waktu Pelaksanaan KKN', [
            XlsxStreamer::column('Kode PT', 'kodept', 12),
            XlsxStreamer::column('Nama PTS', 'nama_pt', 40),
            XlsxStreamer::column('Lokasi', fn ($row) => implode(', ', $this->lokasiPerPt[$row->kodept] ?? []), 40),
            XlsxStreamer::column('Tanggal Mulai', 'tanggal_mulai', 14, 'date'),
            XlsxStreamer::column('Tanggal Selesai', 'tanggal_selesai', 14, 'date'),
            XlsxStreamer::column('Durasi (hari)', fn ($row) => (int) $row->durasi_hari, 12),
            XlsxStreamer::column('Terakhir Diubah', 'updated_at', 18, 'datetime'),
        ]))
            ->info(['Kata kunci' => $this->keyword])
            ->download($filename, $this->rows);
    }
}
