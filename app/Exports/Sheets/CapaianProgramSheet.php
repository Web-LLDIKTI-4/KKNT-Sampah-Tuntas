<?php

namespace App\Exports\Sheets;

use App\Models\Kpisampah;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Capaian Program (dashboard admin): lokasi (biru) -> kecamatan (kuning) -> kelurahan; 1 baris kosong antar lokasi.
 * Kolom: Nama | Persentase Pengurangan Sampah (%) | Klaster. Data dari KpiSampahService::capaianProgram().
 */
class CapaianProgramSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    // Biru gelap agar teks putih kontras
    public const FILL_LOKASI = '4472C4';

    public const FILL_KECAMATAN = 'FFFF00';

    // Nomor baris (1-based) => jenis: lokasi | kecamatan
    private array $jenis = [];

    // Nomor baris kosong pemisah antar lokasi
    private array $sekat = [];

    // Sel klaster kelurahan => warna klaster
    private array $warna = [];

    private int $barisAkhir = 2;

    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'Capaian Program';
    }

    public function array(): array
    {
        $judul = 'Capaian Program — '.($this->data['bulan'] ? Carbon::parse($this->data['bulan'].'-01')->translatedFormat('F Y') : 'Semua Bulan');
        if ($this->data['klaster']) {
            $judul .= ' — Klaster '.Kpisampah::KLASTER[$this->data['klaster']]['label'];
        }
        $rows = [[$judul, null, null], ['Nama', 'Persentase Pengurangan Sampah (%)', 'Klaster']];

        foreach ($this->data['lokasi']->values() as $i => $lokasi) {
            if ($i > 0) {
                $rows[] = [null, null, null];
                $this->sekat[] = count($rows);
            }
            $rows[] = $this->baris($lokasi->nama_lokasi, $lokasi->persen, $lokasi->klaster, count($rows) + 1, 'lokasi');
            foreach ($lokasi->kecamatan as $kec) {
                $rows[] = $this->baris($kec->kecamatan, $kec->persen, $kec->klaster, count($rows) + 1, 'kecamatan');
                foreach ($this->data['kelurahan'][$lokasi->id_lokasi.'|'.$kec->id_kecamatan] ?? [] as $kel) {
                    $rows[] = $this->baris($kel->desa, $kel->persen, $kel->klaster, count($rows) + 1);
                }
            }
        }
        $this->barisAkhir = count($rows);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $akhir = $this->barisAkhir;

            $sheet->mergeCells('A1:C1');
            $sheet->getStyle('A1:C2')->getFont()->setBold(true);
            $sheet->getStyle("A2:C$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("B3:B$akhir")->getNumberFormat()->setFormatCode('0.00%');

            $fill = ['lokasi' => self::FILL_LOKASI, 'kecamatan' => self::FILL_KECAMATAN];
            // Sekat antar lokasi: merge A:C, tanpa border; fill putih menutup gridline Excel
            foreach ($this->sekat as $baris) {
                $sheet->mergeCells("A$baris:C$baris");
                $sheet->getStyle("A$baris:C$baris")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                $sheet->getStyle("A$baris:C$baris")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            }
            foreach ($this->jenis as $baris => $jenis) {
                $sheet->getStyle("A$baris:C$baris")->getFont()->setBold(true);
                $sheet->getStyle("A$baris:C$baris")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill[$jenis]);
                if ($jenis === 'lokasi') {
                    $sheet->getStyle("A$baris:C$baris")->getFont()->getColor()->setRGB('FFFFFF');
                }
            }
            foreach ($this->warna as $sel => $rgb) {
                $sheet->getStyle($sel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
            }
            foreach (['A' => 34, 'B' => 22, 'C' => 12] as $kolom => $lebar) {
                $sheet->getColumnDimension($kolom)->setWidth($lebar);
            }
        }];
    }

    public function jenisBaris(): array
    {
        return $this->jenis;
    }

    private function baris(string $nama, ?float $persen, ?string $klaster, int $nomor, ?string $jenis = null): array
    {
        // Sel klaster kelurahan diwarnai klaster; "-" tanpa fill; lokasi/kecamatan memakai fill barisnya
        if ($klaster && ! $jenis) {
            $this->warna["C$nomor"] = Kpisampah::KLASTER[$klaster]['rgb'];
        }
        if ($jenis) {
            $this->jenis[$nomor] = $jenis;
        }
        return [
            $nama,
            $persen === null ? '-' : round($persen / 100, 4),
            $klaster ? Kpisampah::KLASTER[$klaster]['label'] : '-',
        ];
    }
}
