<?php

namespace App\Exports;

use App\Models\KategoriKegiatan;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KategoriKegiatanExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $data = KategoriKegiatan::all();
        return $data->map(function ($item, $key) {
            return [
                'no' => $key + 1,
                'nama_kategori' => $item->nama_kategori,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No.',
            'Kategori Kegiatan',
        ];
    }
}
