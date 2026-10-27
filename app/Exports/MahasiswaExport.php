<?php

namespace App\Exports;

use App\Support\XlsxStreamer;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Rows come from MahasiswaController::listQuery() so values match the table
class MahasiswaExport
{
    public function download(Collection $rows, string $filename): StreamedResponse
    {
        return (new XlsxStreamer('Data Mahasiswa', $this->columns()))
            ->info(['Data' => $rows->count().' baris terpilih'])
            ->download($filename, $rows);
    }

    // Table's current view; rows may be a LazyCollection (streamed)
    public function downloadTable(iterable $rows, string $filename, array $info = []): StreamedResponse
    {
        return (new XlsxStreamer('Data Mahasiswa', $this->columns()))
            ->info($info)
            ->download($filename, $rows);
    }

    private function columns(): array
    {
        return [
            XlsxStreamer::column('NIM', 'nim', 18),
            XlsxStreamer::column('Nama', 'nama', 30),
            XlsxStreamer::column('Email', 'email', 32),
            XlsxStreamer::column('HP', 'phone', 16),
            XlsxStreamer::column('Perguruan Tinggi', fn ($row) => $row->nm_lemb ?? 'Belum Terdata', 40),
            XlsxStreamer::column('Lokasi Program KKN', fn ($row) => $row->nama_lokasi ?? 'Belum Terdata', 30),
            XlsxStreamer::column('Status Ketua Kelompok', fn ($row) => $row->ketua_kelompok ? 'Ya' : 'Tidak', 14),
            XlsxStreamer::column('Kecamatan', fn ($row) => $row->nama_kecamatan ?? 'Belum diisi', 25),
            XlsxStreamer::column('Kelurahan/Desa', fn ($row) => $row->nama_desa ?? 'Belum diisi', 25),
        ];
    }
}
