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
                'kegiatan' => $item->kegiatan,
                'target' => round((float) $item->target),
                'satuan' => $item->satuan,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama KPI',
            'Kegiatan',
            'Target',
            'Satuan'
        ];
    }
}
