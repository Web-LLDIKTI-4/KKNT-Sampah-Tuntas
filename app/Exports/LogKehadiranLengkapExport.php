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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Kehadiran seluruh mahasiswa dalam cakupan role: admin/kepala/pemda semua, pt mahasiswa PT-nya, dpl bimbingan.
 */
class LogKehadiranLengkapExport extends DefaultValueBinder implements WithCustomValueBinder, FromQuery, WithMapping, WithHeadings, WithEvents, WithStrictNullComparison, WithColumnWidths
{
    public function __construct(private User $user, private ?string $bulan = null) {}

    public function query(): Builder
    {
        return DB::table('kehadiran as k')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'k.email')
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'm.kodept')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();
                $q->whereBetween('k.tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->orderBy('k.tanggal')->orderBy('m.nama')->orderBy('k.id_kehadiran')
            ->select('k.email', 'k.tanggal', 'k.status_kehadiran', 'k.waktu_masuk', 'k.waktu_pulang', 'm.nama', 'm.nim', 'm.phone', 'sp.nm_lemb');
    }

    public function map($row): array
    {
        return [
            $row->email,
            $row->nama,
            $row->phone,
            $row->nim,
            $row->nm_lemb,
            $row->tanggal ? Carbon::parse($row->tanggal)->format('d-m-Y') : null,
            ucfirst((string) $row->status_kehadiran),
            $row->waktu_masuk ? Carbon::parse($row->waktu_masuk)->format('H:i:s') : '-',
            $row->waktu_pulang ? Carbon::parse($row->waktu_pulang)->format('H:i:s') : '-',
        ];
    }

    public function headings(): array
    {
        return ['Email Address', 'Nama Mahasiswa', 'Nomor Kontak', 'NIM', 'Nama Perguruan Tinggi', 'Tanggal', 'Status Kehadiran', 'Jam Masuk', 'Jam Pulang'];
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 28, 'C' => 16, 'D' => 16, 'E' => 30, 'F' => 12, 'G' => 16, 'H' => 10, 'I' => 10];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->getStyle('A1:I1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            ]);
            $sheet->getStyle('A1:I'.$sheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
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

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'admin', 'kepala', 'pemda' => null,
            'pt', 'dpl' => $query->whereIn('k.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
