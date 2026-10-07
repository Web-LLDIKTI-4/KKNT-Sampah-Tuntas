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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
            [$isi, $merges] = self::grupBulan($grupBulan, $baris);
            array_push($rows, ...$isi);
            array_push($this->merges, ...$merges);
            $baris += count($isi);
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

            self::gaya($sheet, 1, $akhir);
            self::lebarKolom($sheet);
            $sheet->freezePane('D2');
        }];
    }

    // Baris A–L satu grup bulan mulai $baris + range merge Bulan (A) & Kecamatan (B)
    public static function grupBulan(object $grupBulan, int $baris): array
    {
        $rows = [];
        $merges = [];
        $awalBulan = $baris;
        foreach ($grupBulan->kecamatan as $kec) {
            $awalKecamatan = $baris;
            foreach ($kec->desa as $r) {
                $rows[] = self::baris($grupBulan, $kec, $r);
                $baris++;
            }
            if ($baris - 1 > $awalKecamatan) {
                $merges[] = "B$awalKecamatan:B".($baris - 1);
            }
        }
        if ($baris - 1 > $awalBulan) {
            $merges[] = "A$awalBulan:A".($baris - 1);
        }

        return [$rows, $merges];
    }

    // Style tabel LLDIKTI: header di baris $header, data s.d. $akhir
    public static function gaya(Worksheet $sheet, int $header, int $akhir): void
    {
        $sheet->getStyle("A$header:L$header")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A$header:L$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $awal = $header + 1;
        if ($akhir >= $awal) {
            $sheet->getStyle("A$awal:B$akhir")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("D$awal:E$akhir")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G$awal:K$akhir")->getNumberFormat()->setFormatCode('#,##0.00');
            foreach (['F', 'L'] as $kolom) {
                $sheet->getStyle("$kolom$awal:$kolom$akhir")->getNumberFormat()->setFormatCode('0.00%');
            }
        }
        $sheet->getRowDimension($header)->setRowHeight(45);
    }

    public static function lebarKolom(Worksheet $sheet): void
    {
        foreach (range('A', 'L') as $kolom) {
            $sheet->getColumnDimension($kolom)->setWidth($kolom === 'C' ? 22 : 16);
        }
    }

    // Isi kolom A–L satu desa; dipakai ulang CapaianKpiPeriodeSheet
    public static function baris(object $grupBulan, object $kec, object $r): array
    {
        return [
            $grupBulan->nama_bulan, $kec->kecamatan ?? '-', $r->desa ?? '-',
            $r->jml_rumah, $r->jml_rumah_memilah, self::pecahan($r->persen_ketaatan),
            $r->organik, $r->anorganik, $r->residu,
            $r->total_terkelola, $r->total_dihasilkan, self::pecahan($r->persen_penurunan),
        ];
    }

    // Persen → pecahan untuk format 0.00%; pembagi 0 → '-'
    public static function pecahan(?float $persen): float|string
    {
        return $persen === null ? '-' : round($persen / 100, 4);
    }
}
