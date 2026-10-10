<?php

namespace App\Exports;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\Logkegiatan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LogHarianByMhsExport extends SafeValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings
{
    protected $emailMahasiswa;

    public function __construct($email)
    {
        $this->emailMahasiswa = $email;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return Logkegiatan::where('email', $this->emailMahasiswa)
            ->whereNotNull('deskripsi')
            ->with(['mahasiswa.sp', 'kpi'])
            ->without('dplmentoring')
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item, $key) => [
                $key + 1,
                Carbon::parse($item->tanggal)->format('d-m-Y'),
                $item->kpi?->nama_kpi,
                strip_tags((string) $item->deskripsi),
                $item->volume,
                $item->satuan,
                $item->tautan,
            ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'KPI',
            'Deskripsi Kegiatan',
            'Volume/Kuantitas Output',
            'Satuan',
            'Tautan Bukti',
        ];
    }
}
