<?php

namespace App\Exports;

use App\Models\Kpi;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KPIExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $data = Kpi::all();
        return $data->map(function ($item, $key) {
            return [
                'no' => $key + 1,
                'nama_kpi' => $item->nama_kpi,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama Aktivitas',
        ];
    }
}
