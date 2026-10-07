<?php

namespace App\Exports;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\PendataanPemilahanSampah;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendataanPemilahanSampahByMhsExport extends SafeValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings
{
    public function __construct(private string $email, private ?string $bulan = null) {}

    public function collection()
    {
        return PendataanPemilahanSampah::where('email', $this->email)
            ->when($this->bulan, fn ($query, $bulan) => $query->whereYear('tanggal', substr($bulan, 0, 4))
                ->whereMonth('tanggal', substr($bulan, 5, 2)))
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get()
            ->map(fn (PendataanPemilahanSampah $row, int $index) => [
                $index + 1,
                Carbon::parse($row->tanggal)->format('d-m-Y'),
                $row->nama_kepala_keluarga,
                $row->alamat_rumah,
                $row->rt,
                $row->rw,
                $row->memilah ? 'Ya' : 'Tidak',
                (float) $row->organik_kg,
                (float) $row->anorganik_kg,
                (float) $row->residu_kg,
            ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Nama Kepala Keluarga',
            'Alamat Rumah',
            'RT',
            'RW',
            'Memilah',
            'Organik (kg)',
            'Anorganik (kg)',
            'Residu (kg)',
        ];
    }
}
