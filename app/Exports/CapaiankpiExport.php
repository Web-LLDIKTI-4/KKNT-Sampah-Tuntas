<?php

namespace App\Exports;

use App\Models\Kpicapaian;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CapaiankpiExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        $kpicapaian = Kpicapaian::all();

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $kpicapaian->map(function ($item, $key) {
            $desa = $item->pjdesa->desa->desa ?? '';
            $pjdesa = $item->email ?? '';
            $kpi = $item->kpi ? $item->kpi->nama_kpi : null;
            $tahapan = $item->target ? $item->target->tahapan : null;
            $target_kpi = $item->target ? $item->target->nama_kpitarget : null;
            
            return [
                'No' => $key + 1, 
                'PJ Desa' => $pjdesa, 
                'Desa' => $desa, 
                'KPI' => $kpi, 
                'Tahapan' => $tahapan, 
                'Target KPI' => $target_kpi,
                'Status' => $item->status_capaian,
                'Tautan' => $item->tautan,
                'Permasalahan' => $item->permasalahan,
                'Solusi' => $item->solusi,
                'Kendala' => $item->kendala,
                // Tambahkan kolom lain sesuai kebutuhan
            ];
        });

        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Tentukan judul kolom
        return [
            'No',
            'PJ Desa',
            'Desa',
            'KPI',
            'Tahapan',
            'Target KPI',
            'Status',
            'Tautan',
            'Permasalahan',
            'Solusi',
            'Kendala',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
