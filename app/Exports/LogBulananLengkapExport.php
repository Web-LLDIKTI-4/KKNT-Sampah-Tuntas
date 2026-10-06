<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Log bulanan seluruh mahasiswa dalam cakupan role: admin/kepala/pemda semua, pt mahasiswa PT-nya, dpl bimbingan.
 */
class LogBulananLengkapExport extends DefaultValueBinder implements WithCustomValueBinder, FromQuery, WithMapping, WithHeadings, WithEvents, WithStrictNullComparison, WithColumnWidths
{
    public function __construct(private User $user, private ?string $bulan = null) {}

    public function query(): Builder
    {
        return DB::table('logkegiatan_bulanan as lb')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'lb.email')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                [$tahun, $bln] = explode('-', $bulan);
                $q->where('lb.tahun', (int) $tahun)->where('lb.bulan', (int) $bln);
            })
            ->orderBy('lb.tahun')->orderBy('lb.bulan')->orderBy('m.nama')->orderBy('lb.id_logbulanan')
            ->select('lb.*', 'm.nama', 'm.nim', 'm.phone', 'sp.nm_lemb');
    }

    public function map($row): array
    {
        return [
            $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i:s') : null,
            $row->email,
            $row->nama,
            $row->phone,
            $row->nim,
            $row->nm_lemb,
            $row->tahun && $row->bulan ? Carbon::create((int) $row->tahun, (int) $row->bulan)->translatedFormat('F Y') : $row->bulan,
            $this->bersihkan($row->deskripsi),
            $row->nilai,
        ];
    }

    public function headings(): array
    {
        return ['Timestamp', 'Email Address', 'Nama Mahasiswa', 'Nomor Kontak', 'NIM', 'Nama Perguruan Tinggi', 'Bulan', 'Deskripsi', 'Nilai'];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 28, 'C' => 28, 'D' => 16, 'E' => 16, 'F' => 30, 'G' => 16, 'H' => 70, 'I' => 8];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $akhir = $sheet->getHighestRow();
            $sheet->getStyle('A1:I1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            ]);
            $sheet->getStyle("A1:I$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("H2:H$akhir")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }];
    }

    // Teks input user berawalan "=" ditulis sebagai string, bukan formula
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && str_starts_with($value, '=')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    // HTML deskripsi → teks biasa (sama dengan LogBulananByMhsExport)
    private function bersihkan(?string $deskripsi): string
    {
        if ($deskripsi === null || $deskripsi === '') {
            return '-';
        }
        $teks = str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $deskripsi);
        $teks = html_entity_decode(strip_tags($teks), ENT_QUOTES | ENT_HTML5);
        $teks = preg_replace('/[ \t]+/', ' ', $teks);

        return preg_replace("/\n{3,}/", "\n\n", trim($teks));
    }

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'admin', 'kepala', 'pemda' => null,
            'pt', 'dpl' => $query->whereIn('lb.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
