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
 * Data sampah per kelurahan (kiri) + total per bulan (kanan, sel di-merge per bulan seperti kolom Bulan).
 * Sel persentase pengurangan diwarnai sesuai klaster (hijau/kuning/merah).
 */
class DataSampahSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    private const HEADINGS = [
        'Bulan', 'Kecamatan', 'Perguruan Tinggi', 'Kelurahan', 'Jumlah RW KBS Dampingan DLH', 'Jumlah RW Non-KBS',
        'Jumlah Rumah Keseluruhan', 'Jumlah Rumah yang Memilah', 'Persentase Ketaatan Pemilahan', 'Jumlah Timbulan Sampah (Kg/Bulan)',
        'Total Pengurangan Organik', 'Total Pengurangan Anorganik', 'Total Pengurangan', 'Residu (Kg/Bulan)',
        'Persentase Pengurangan Sampah (%)', 'Jumlah Bank Sampah',
        'Total Jumlah Rumah Keseluruhan', 'Total Jumlah Memilah', 'Persentase Ketaatan Pemilahan', 'Jumlah Timbulan Sampah (Kg/Bulan)',
        'Total Pengurangan Organik', 'Total Pengurangan Anorganik', 'Total Pengurangan', 'Residu (Kg/Bulan)', 'Jumlah Bank Sampah',
        'Persentase Pengurangan Sampah (%)',
    ];

    // Kolom total kecamatan (di-merge per kecamatan per bulan)
    private const KOLOM_TOTAL = ['Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    private const FORMAT = [
        '#,##0' => ['E', 'F', 'G', 'H', 'P', 'Q', 'R', 'Y'],
        '#,##0.00' => ['J', 'K', 'L', 'M', 'N', 'T', 'U', 'V', 'W', 'X'],
        '0.00%' => ['I', 'O', 'S', 'Z'],
    ];

    private array $merges = [];

    // Sel => warna klaster persentase pengurangan sampah
    private array $warna = [];

    private int $barisAkhir = 1;

    public function __construct(private Collection $detail, private Collection $rekapKecamatan) {}

    public function title(): string
    {
        return 'Data Sampah';
    }

    public function array(): array
    {
        $total = $this->totalPerBulan();
        $rows = [self::HEADINGS];
        $baris = 2;

        $urut = $this->detail->sortBy([['bulan', 'asc'], ['kecamatan', 'asc'], ['nama_pt', 'asc'], ['desa', 'asc']]);
        foreach ($urut->groupBy('bulan') as $bulan => $perBulan) {
            $awalBulan = $baris;
            $t = $total[$bulan] ?? null;
            foreach ($perBulan->groupBy('id_kecamatan') as $perKecamatan) {
                $awalKecamatan = $baris;
                foreach ($perKecamatan->values() as $r) {
                    $rows[] = [
                        Carbon::parse($bulan)->translatedFormat('F Y'), $r->kecamatan,
                        // Mode per ketua: nama ketua ikut di kolom PT (struktur kolom tetap)
                        ($r->nama_pt ?? '-').(isset($r->email) ? ' – '.($r->nama_ketua ?? $r->email) : ''), $r->desa,
                        (int) $r->jml_rw_kbs, (int) $r->jml_rw_non_kbs, (int) $r->jml_rumah, (int) $r->jml_rumah_memilah,
                        $this->pecahan($r->persen_ketaatan), (float) $r->timbulan, (float) $r->pengurangan_organik,
                        (float) $r->pengurangan_anorganik, (float) $r->pengurangan, (float) $r->residu,
                        $this->pecahan($r->persen_pengurangan), (int) $r->jml_bank_sampah,
                        ...($baris === $awalBulan && $t ? $this->kolomTotal($t) : array_fill(0, count(self::KOLOM_TOTAL), null)),
                    ];
                    $this->warnai("O$baris", $r->persen_pengurangan);
                    $baris++;
                }
                $this->merge('B', $awalKecamatan, $baris - 1);
            }
            $this->warnai("Z$awalBulan", $t?->persen_pengurangan);
            foreach (['A', ...self::KOLOM_TOTAL] as $kolom) {
                $this->merge($kolom, $awalBulan, $baris - 1);
            }
        }
        $this->barisAkhir = $baris - 1;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $akhir = max($this->barisAkhir, 1);

            foreach ($this->merges as $range) {
                $sheet->mergeCells($range);
            }

            $sheet->getStyle('A1:Z1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            ]);
            $sheet->getStyle('Q1:Z1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');
            $sheet->getStyle('A1:Z1')->getAlignment()->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(60);

            $sheet->getStyle("A1:Z$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A2:Z$akhir")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("Z2:Z$akhir")->getFont()->setBold(true);
            foreach ($this->warna as $sel => $rgb) {
                $sheet->getStyle($sel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
            }

            foreach (self::FORMAT as $format => $kolomList) {
                foreach ($kolomList as $kolom) {
                    $sheet->getStyle("{$kolom}2:$kolom$akhir")->getNumberFormat()->setFormatCode($format);
                }
            }

            foreach (['A' => 16, 'B' => 18, 'C' => 30, 'D' => 22] as $kolom => $lebar) {
                $sheet->getColumnDimension($kolom)->setWidth($lebar);
            }
            foreach (range('E', 'Z') as $kolom) {
                $sheet->getColumnDimension($kolom)->setWidth(14);
            }
            $sheet->freezePane('E2');
        }];
    }

    // Total per bulan = jumlah semua kecamatan (sesuai filter export); persen dihitung ulang dari total
    private function totalPerBulan(): Collection
    {
        $kolom = ['jml_rumah', 'jml_rumah_memilah', 'timbulan', 'pengurangan_organik', 'pengurangan_anorganik', 'pengurangan', 'residu', 'jml_bank_sampah'];

        return $this->rekapKecamatan->groupBy('bulan')->map(function (Collection $rows) use ($kolom) {
            $t = new \stdClass;
            foreach ($kolom as $k) {
                $t->{'total_'.$k} = round($rows->sum(fn ($r) => (float) $r->{'total_'.$k}), 2);
            }
            $t->persen_ketaatan = Kpisampah::persen($t->total_jml_rumah_memilah, $t->total_jml_rumah);
            $t->persen_pengurangan = Kpisampah::persen($t->total_pengurangan, $t->total_timbulan);

            return $t;
        });
    }

    private function kolomTotal(object $t): array
    {
        return [
            (int) $t->total_jml_rumah, (int) $t->total_jml_rumah_memilah, $this->pecahan($t->persen_ketaatan),
            (float) $t->total_timbulan, (float) $t->total_pengurangan_organik, (float) $t->total_pengurangan_anorganik,
            (float) $t->total_pengurangan, (float) $t->total_residu, (int) $t->total_jml_bank_sampah,
            $this->pecahan($t->persen_pengurangan),
        ];
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
