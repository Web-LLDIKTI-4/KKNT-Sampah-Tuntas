<?php

namespace App\Exports;

use App\Models\Kpitarget;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KPITargetExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = Kpitarget::with('kpi')->get();
        return $data->map(function ($item) {
            return [
                'nama_kpi' => $item->kpi->nama_kpi,
                'tahapan' => $item->tahapan,
                'nama_kpitarget' => $item->nama_kpitarget,
                'persen' => $item->persen,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama KPI',
            'Tahapan',
            'Target KPI',
            'Persentase (%)'
        ];
    }
}
