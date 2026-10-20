<?php

namespace App\Exports;

use App\Support\XlsxStreamer;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Tabel Data Peserta halaman login; $rows = hasil (cache) DataPesertaSummary::all()
class DataPesertaExport
{
    private const COUNTS = ['jumlah_mahasiswa', 'jumlah_dpl', 'jumlah_pic', 'jumlah_ketua_kelompok'];

    public function __construct(private Collection $rows) {}

    public function download(string $filename): StreamedResponse
    {
        return (new XlsxStreamer('Data Peserta', [
            XlsxStreamer::column('Kode PT', 'kodept', 12),
            XlsxStreamer::column('Nama PTS', 'nama_pt', 45),
            XlsxStreamer::column('Jml. Mahasiswa', 'jumlah_mahasiswa', 15),
            XlsxStreamer::column('Jml. DPL', 'jumlah_dpl', 12),
            XlsxStreamer::column('Jml. PIC', 'jumlah_pic', 12),
            XlsxStreamer::column('Jml. Ketua Kelompok', 'jumlah_ketua_kelompok', 18),
        ]))
            ->total(array_combine(self::COUNTS, array_map(fn ($key) => (int) $this->rows->sum($key), self::COUNTS)))
            ->download($filename, $this->rows);
    }
}
