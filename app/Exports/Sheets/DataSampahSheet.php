<?php

namespace App\Exports\Sheets;

use App\Models\Kpisampah;
use Illuminate\Support\Carbon;
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
 * Data sampah per kelurahan sesuai "FORMAT DATA PENGELOLAAN SAMPAH FINAL.xlsx":
 * header 4 baris bertingkat (A1:W4), data mulai baris 5, catatan asumsi di bawah data.
 * PT (dan ketua untuk role PT) ditulis di awal Keterangan karena format tidak punya kolom PT.
 */
class DataSampahSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    private const BARIS_DATA = 5;

    // Sel => teks header
    private const HEADER = [
        'A1' => 'Bulan',
        'B1' => 'Kecamatan',
        'C1' => 'Desa/Kelurahan',
        'D1' => 'Jumlah RW',
        'E1' => 'Jumlah Penduduk (Jiwa)',
        'F1' => 'Jumlah Rumah Keseluruhan',
        'G1' => 'Jumlah Rumah yang memilah',
        'H1' => 'Persentase Ketaatan Pemilahan [(G/F)*100%]',
        'I1' => "Jumlah Timbulan Sampah \n(Kg/Bulan)\n[E*Kof timbulan*Hari]",
        'J1' => 'Jenis Sampah yang diolah',
        'V1' => "Persentase Penurunan Sampah\n[(T/I)*100%]",
        'W1' => 'Keterangan',
        'J2' => 'Organik',
        'P2' => 'Anorganik',
        'T2' => "Total Pengolahan (Kg/Bulan)\n[J+M+P]",
        'U2' => "Sampah Belum Terkelola (Kg/Bulan)\n[I-T]",
        'J3' => 'Diolah di sumber (Kg/Bulan)',
        'K3' => 'Nama Metode Pengolahan Sampah Organik (maggot/komposting/dsb)',
        'L3' => 'Jumlah Metode Pengolahan Sampah Organik (unit)',
        'M3' => 'Diolah oleh DLH (Kg/Bulan)',
        'N3' => 'Nama Fasilitas Pengolahan di DLH (POO/TPST/TPS3R/PDU)',
        'O3' => 'Lokasi Fasilitas',
        'P3' => 'Diolah di sumber (Kg/Bulan)',
        'Q3' => "Nama Metode Pengolahan Sampah Anorganik \n(Bank Sampah/Pengepul/dsb)",
        'R3' => 'Lokasi Metode',
        'S3' => "Jumlah Metode Pengolahan Sampah Anorganik \n(unit)",
    ];

    private const MERGE_HEADER = [
        'A1:A4', 'B1:B4', 'C1:C4', 'D1:D4', 'E1:E4', 'F1:F4', 'G1:G4', 'H1:H4', 'I1:I4', 'V1:V4', 'W1:W4',
        'J1:U1', 'J2:O2', 'P2:S2', 'T2:T4', 'U2:U4',
        'J3:J4', 'K3:K4', 'L3:L4', 'M3:M4', 'N3:N4', 'O3:O4', 'P3:P4', 'Q3:Q4', 'R3:R4', 'S3:S4',
    ];

    private const CATATAN = [
        'Asumsi : Timbulan sampah disesuaikan dengan koefisien timbulan sampah di masing-masing kab/kota (…/kg/org/hari)',
        'Sumber: tuliskan sumber asumsi yang digunakan (contoh: dokumen RPS)',
        'untuk baseline mohon diisi data mulai bulan Agustus dan September',
    ];

    private const FORMAT = [
        '#,##0' => ['D', 'E', 'F', 'G', 'L', 'S'],
        '#,##0.00' => ['I', 'J', 'M', 'P', 'T', 'U'],
        '0.00%' => ['H', 'V'],
    ];

    private const LEBAR = [
        'A' => 12.54, 'B' => 12.54, 'C' => 14.63, 'D' => 12.54, 'E' => 12.54, 'F' => 12.54, 'G' => 12.54, 'H' => 12.54,
        'I' => 18.63, 'J' => 15.54, 'K' => 21.63, 'L' => 18.91, 'M' => 14.18, 'N' => 20.54, 'O' => 16.91, 'P' => 14.82,
        'Q' => 23.18, 'R' => 15.91, 'S' => 23.18, 'T' => 12.54, 'U' => 12.54, 'V' => 12.54, 'W' => 27,
    ];

    private array $merges = [];

    // Sel => warna klaster persentase penurunan sampah
    private array $warna = [];

    private int $barisAkhir = self::BARIS_DATA - 1;

    public function __construct(private Collection $detail) {}

    public function title(): string
    {
        return 'Data Sampah';
    }

    public function array(): array
    {
        $rows = $this->header();
        $baris = self::BARIS_DATA;

        $urut = $this->detail->sortBy([['bulan', 'asc'], ['kecamatan', 'asc'], ['desa', 'asc'], ['nama_pt', 'asc']]);
        foreach ($urut->groupBy('bulan') as $bulan => $perBulan) {
            $awalBulan = $baris;
            foreach ($perBulan->groupBy('id_kecamatan') as $perKecamatan) {
                $awalKecamatan = $baris;
                foreach ($perKecamatan->values() as $r) {
                    $rows[] = [
                        Carbon::parse($bulan)->translatedFormat('F Y'), $r->kecamatan, $r->desa,
                        (int) $r->jml_rw, (int) $r->jml_penduduk, (int) $r->jml_rumah, (int) $r->jml_rumah_memilah,
                        $this->pecahan($r->persen_ketaatan), (float) $r->timbulan,
                        (float) $r->organik_sumber, $r->organik_metode, (int) $r->organik_metode_unit,
                        (float) $r->organik_dlh, $r->organik_dlh_fasilitas, $r->organik_dlh_lokasi,
                        (float) $r->anorganik_sumber, $r->anorganik_metode, $r->anorganik_metode_lokasi, (int) $r->anorganik_metode_unit,
                        (float) $r->pengurangan, (float) $r->belum_terkelola,
                        $this->pecahan($r->persen_pengurangan), $this->keterangan($r),
                    ];
                    $this->warnai("V$baris", $r->persen_pengurangan);
                    $baris++;
                }
                $this->merge('B', $awalKecamatan, $baris - 1);
            }
            $this->merge('A', $awalBulan, $baris - 1);
        }
        $this->barisAkhir = $baris - 1;

        // Catatan: 2 baris kosong setelah data (A11-A13 pada format asli)
        array_push($rows, [null], [null]);
        foreach (self::CATATAN as $teks) {
            $rows[] = [$teks];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $akhir = $this->barisAkhir;
            $catatan = max($akhir, self::BARIS_DATA - 1) + 3;

            foreach ([...self::MERGE_HEADER, ...$this->merges, "A$catatan:E$catatan"] as $range) {
                $sheet->mergeCells($range);
            }

            $sheet->getStyle('A1:W4')->applyFromArray([
                'font' => ['bold' => true, 'size' => 8],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDE59B']],
                'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            foreach ([1 => 10.5, 2 => 10.5, 3 => 12.5, 4 => 30] as $no => $tinggi) {
                $sheet->getRowDimension($no)->setRowHeight($tinggi);
            }

            if ($akhir >= self::BARIS_DATA) {
                $sheet->getStyle('A'.self::BARIS_DATA.":W$akhir")->applyFromArray([
                    'font' => ['size' => 8],
                    'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                foreach (self::FORMAT as $format => $kolomList) {
                    foreach ($kolomList as $kolom) {
                        $sheet->getStyle($kolom.self::BARIS_DATA.":$kolom$akhir")->getNumberFormat()->setFormatCode($format);
                    }
                }
                foreach ($this->warna as $sel => $rgb) {
                    $sheet->getStyle($sel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
                }
            }

            $sheet->getStyle("A$catatan:A".($catatan + 1))->getFont()->setBold(true);
            $sheet->getStyle("A$catatan:A".($catatan + 2))->getFont()->setSize(8);
            $sheet->getStyle("A$catatan")->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($catatan)->setRowHeight(25);

            foreach (self::LEBAR as $kolom => $lebar) {
                $sheet->getColumnDimension($kolom)->setWidth($lebar);
            }
            $sheet->freezePane('D'.self::BARIS_DATA);
        }];
    }

    // 4 baris header; sel yang tertutup merge dibiarkan null
    private function header(): array
    {
        $rows = array_fill(1, 4, array_fill(0, 23, null));
        foreach (self::HEADER as $sel => $teks) {
            $rows[(int) substr($sel, 1)][ord($sel[0]) - ord('A')] = $teks;
        }

        return array_values($rows);
    }

    // PT (+ ketua untuk role PT) di depan keterangan isian
    private function keterangan(object $r): ?string
    {
        $pt = ($r->nama_pt ?? null) ? 'PT: '.$r->nama_pt.(isset($r->email) ? ' – '.($r->nama_ketua ?? $r->email) : '') : null;

        return implode('. ', array_filter([$pt, $r->keterangan ?? null])) ?: null;
    }

    // Persen disimpan sebagai pecahan agar format % Excel benar
    private function pecahan($persen): ?float
    {
        return $persen === null ? null : round((float) $persen / 100, 4);
    }

    private function warnai(string $sel, $persen): void
    {
        if ($klaster = Kpisampah::klaster($persen)) {
            $this->warna[$sel] = Kpisampah::KLASTER[$klaster]['rgb'];
        }
    }

    private function merge(string $kolom, int $awal, int $akhir): void
    {
        if ($akhir > $awal) {
            $this->merges[] = "$kolom$awal:$kolom$akhir";
        }
    }
}
