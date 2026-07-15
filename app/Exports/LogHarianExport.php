<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

use App\Models\Logkegiatan;

use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;

class LogHarianExport implements FromCollection, WithHeadings, WithStyles
{
    protected $email;
    private $index = 0;
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Logkegiatan::where('email', $this->email)
            ->get()
            ->map(function ($item, $key) {
                return [
                    'No' => $key + 1,
                    'Tanggal' => \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
                    'Deskripsi' => strip_tags($item->deskripsi),
                    'Tautan' => $item->tautan,
                    'Volume' => $item->volume,
                    'Satuan' => $item->satuan,
                    'KPI' => $item->kpi->nama_kpi,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Deskripsi',
            'Tautan',
            'Volume',
            'Satuan',
            'KPI',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('C')->getAlignment()->setWrapText(true);

        return [];
    }
}
