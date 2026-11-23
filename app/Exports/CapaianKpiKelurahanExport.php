<?php

namespace App\Exports;

use App\Models\PenguranganSampah;
use App\Support\XlsxStreamer;
use Generator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Capaian KPI Kelurahan/Desa: 1 baris per PTS (data kelurahan diulang); kelurahan tanpa PTS = 1 baris, kolom PTS "-"
class CapaianKpiKelurahanExport
{
    public function __construct(private Collection $kelurahan, private array $info = []) {}

    public function download(string $filename): StreamedResponse
    {
        $klaster = fn (?string $k) => PenguranganSampah::KLASTER[$k]['label'] ?? null;

        return (new XlsxStreamer('Capaian KPI Kelurahan/Desa', [
            XlsxStreamer::column('Kabupaten/Kota', 'kel.kab_kota', 22),
            XlsxStreamer::column('Kecamatan', 'kel.kecamatan', 22),
            XlsxStreamer::column('Kelurahan/Desa', 'kel.desa', 24),
            XlsxStreamer::column('Baseline', fn ($r) => $r->kel->baseline === null ? null : PenguranganSampah::formatBaseline($r->kel->baseline, $r->kel->satuan_baseline), 12),
            XlsxStreamer::column('Ketaatan Pemilahan (%)', 'kel.persen_ketaatan', 14, 'percent'),
            XlsxStreamer::column('Penurunan Timbulan Sampah Kelurahan (%)', 'kel.persen', 18, 'percent'),
            XlsxStreamer::column('Klaster Kelurahan', fn ($r) => $klaster($r->kel->klaster), 12),
            XlsxStreamer::column('PTS', 'pt.nama_pt', 40),
            XlsxStreamer::column('Mahasiswa', 'pt.jumlah_mahasiswa', 11),
            XlsxStreamer::column('DPL', 'pt.jumlah_dpl', 8),
            XlsxStreamer::column('Penurunan Timbulan Sampah PTS (%)', 'pt.persen', 18, 'percent'),
            XlsxStreamer::column('Klaster PTS', fn ($r) => $klaster($r->pt?->klaster), 12),
        ]))
            ->info($this->info)
            ->download($filename, $this->rows());
    }

    private function rows(): Generator
    {
        foreach ($this->kelurahan as $kel) {
            foreach ($kel->pt->isEmpty() ? [null] : $kel->pt as $pt) {
                yield (object) ['kel' => $kel, 'pt' => $pt];
            }
        }
    }
}
