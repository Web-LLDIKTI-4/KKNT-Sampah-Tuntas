<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Rekap sampah format LLDIKTI (A–L) dari KpiSampahService::rekapLldikti(); Bulan & Kecamatan di-merge per grup.
 */
class RekapLldiktiSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    public const HEADER = [
        'Bulan',
        'Nama Kecamatan',
        'Nama Desa/Kelurahan',
        'Jumlah Rumah Keseluruhan',
        'Jumlah Rumah yang memilah',
        'Persentase Ketaatan Pemilahan [(E/D)*100%]',
        'Jumlah Sampah Organik Terkelola (Kg)',
        'Jumlah Sampah Anorganik Terkelola (Kg)',
        'Jumlah Sampah Residu (Kg)',
        'Total Sampah Terkelola (Kg) [G+H]',
        'Total Sampah Dihasilkan (Kg) [G+H+I]',
        'Persentase Penurunan Sampah [(J/K)*100%]',
    ];

    private array $merges = [];

    private int $barisAkhir = 1;

    public function __construct(private Collection $rekap) {}

    public function title(): string
    {
        return 'Rekap Sampah';
    }

    public function array(): array
    {
        $rows = [self::HEADER];
        $baris = 2;

        foreach ($this->rekap as $grupBulan) {
            $awalBulan = $baris;
            foreach ($grupBulan->kecamatan as $kec) {
                $awalKecamatan = $baris;
                foreach ($kec->desa as $r) {
                    $rows[] = [
                        $grupBulan->nama_bulan, $kec->kecamatan ?? '-', $r->desa ?? '-',
                        $r->jml_rumah, $r->jml_rumah_memilah, $this->pecahan($r->persen_ketaatan),
                        $r->organik, $r->anorganik, $r->residu,
                        $r->total_terkelola, $r->total_dihasilkan, $this->pecahan($r->persen_penurunan),
                    ];
                    $baris++;
                }
                $this->merge('B', $awalKecamatan, $baris - 1);
            }
            $this->merge('A', $awalBulan, $baris - 1);
        }
        $this->barisAkhir = $baris - 1;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $akhir = $this->barisAkhir;

            foreach ($this->merges as $range) {
                $sheet->mergeCells($range);
            }

            $sheet->getStyle('A1:L1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A1:L$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            if ($akhir >= 2) {
                $sheet->getStyle("A2:B$akhir")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle("D2:E$akhir")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("G2:K$akhir")->getNumberFormat()->setFormatCode('#,##0.00');
                foreach (['F', 'L'] as $kolom) {
                    $sheet->getStyle("{$kolom}2:$kolom$akhir")->getNumberFormat()->setFormatCode('0.00%');
                }
            }

            $sheet->getRowDimension(1)->setRowHeight(45);
            foreach (range('A', 'L') as $kolom) {
                $sheet->getColumnDimension($kolom)->setWidth($kolom === 'C' ? 22 : 16);
            }
            $sheet->freezePane('D2');
        }];
    }

    private function merge(string $kolom, int $awal, int $akhir): void
    {
        if ($akhir > $awal) {
            $this->merges[] = "$kolom$awal:$kolom$akhir";
        }
    }

    // Persen → pecahan untuk format 0.00%; pembagi 0 → '-'
    private function pecahan(?float $persen): float|string
    {
        return $persen === null ? '-' : round($persen / 100, 4);
    }
}
