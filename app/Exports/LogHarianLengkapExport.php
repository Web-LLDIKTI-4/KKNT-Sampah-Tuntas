<?php

namespace App\Exports;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Log harian lengkap, di-scope per role: admin/kepala/pemda semua, pt mahasiswa PT-nya,
 * dpl mahasiswa bimbingan, mahasiswa datanya sendiri.
 */
class LogHarianLengkapExport extends SafeValueBinder implements WithCustomValueBinder, FromQuery, WithMapping, WithHeadings, WithEvents, WithStrictNullComparison, WithColumnWidths
{
    public function __construct(private User $user, private ?string $bulan = null)
    {
    }

    public function query(): Builder
    {
        $lokasiTerakhir = DB::table('mahasiswa_lokasi')
            ->whereNotNull('id_desa')
            ->groupBy('id_mahasiswa')
            ->selectRaw('id_mahasiswa, MAX(tahun) as tahun');

        return DB::table('logkegiatan as l')
            ->whereNotNull('l.deskripsi')
            ->leftJoin('mahasiswa as m', 'm.email', '=', 'l.email')
            ->leftJoin('kpi as k', 'k.id_kpi', '=', 'l.id_kpi')
            ->leftJoinSub($lokasiTerakhir, 'lt', 'lt.id_mahasiswa', '=', 'm.id_mahasiswa')
            ->leftJoin('mahasiswa_lokasi as ml', fn ($j) => $j->on('ml.id_mahasiswa', '=', 'lt.id_mahasiswa')->on('ml.tahun', '=', 'lt.tahun'))
            ->leftJoin('desa as d', 'd.id_desa', '=', 'ml.id_desa')
            ->leftJoin('kecamatan as kc', 'kc.id_kecamatan', '=', 'd.id_kecamatan')
            ->leftJoin('lokasi_program as lp', 'lp.id', '=', 'm.location_program')
            ->tap(fn (Builder $q) => $this->scope($q))
            ->when($this->bulan, function (Builder $q, $bulan) {
                $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();
                $q->whereBetween('l.tanggal', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->orderBy('l.tanggal')->orderBy('l.created_at')->orderBy('l.id_log')
            ->select('l.*', 'm.nama', 'm.phone', 'lp.nama_lokasi', 'kc.kecamatan', 'd.desa', 'k.nama_kpi');
    }

    public function map($row): array
    {
        return [
            $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i:s') : null,
            $row->email,
            $row->nama,
            $row->phone,
            Carbon::parse($row->tanggal)->format('d/m/Y'),
            $row->nama_lokasi,
            $row->kecamatan,
            $row->desa,
            strip_tags((string) $row->deskripsi),
            $row->volume,
            $row->satuan,
            $row->nama_kpi,
            $row->tautan,
        ];
    }

    public function headings(): array
    {
        return [
            'Timestamp', 'Email Address', 'Nama Mahasiswa Penginput Data', 'Nomor Kontak', 'Tanggal',
            'Kabupaten/Kota', 'Nama Kecamatan', 'Nama Kelurahan/Desa', 'Deskripsi Kegiatan',
            'Volume', 'Satuan', 'KPI', 'Tautan Bukti',
        ];
    }

    // Lebar manual: ShouldAutoSize menghitung tiap sel (boros memori)
    public function columnWidths(): array
    {
        return [
            'A' => 20, 'B' => 28, 'C' => 28, 'D' => 16, 'E' => 12, 'F' => 20, 'G' => 20, 'H' => 22,
            'I' => 45, 'J' => 14, 'K' => 18, 'L' => 24, 'M' => 35,
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->getStyle('A1:M1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            ]);
            $sheet->getStyle('A1:M'.$sheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }];
    }

    // Anti-IDOR: scope wajib di query, bukan dari input
    private function scope(Builder $query): void
    {
        match ($this->user->role) {
            'admin', 'kepala', 'pemda' => null,
            'mahasiswa' => $query->where('l.email', $this->user->email),
            'pt', 'dpl' => $query->whereIn('l.email', Mahasiswa::visibleTo($this->user)->whereNotNull('email')->select('email')->toBase()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
