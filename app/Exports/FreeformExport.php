<?php

namespace App\Exports;

use App\Models\Freeform;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FreeformExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        $structureform = Freeform::with(['mahasiswa'])->get();

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $structureform->map(function ($item, $key) {
            $nim = $item->mahasiswa ? $item->mahasiswa->nim : null;
            $nama = $item->mahasiswa ? $item->mahasiswa->nama : null;
            $prodi = $item->mahasiswa ? $item->mahasiswa->prodi : null;
            $nama_lemb = $item->mahasiswa->sp ? $item->mahasiswa->sp->nm_lemb : null;

            $nilai_dpl = is_numeric($item->nilai_dpl) ? $item->nilai_dpl : 0;
            $nilai_dpa = is_numeric($item->nilai_dpa) ? $item->nilai_dpa : 0;
            $nilai_akhir = ($nilai_dpl+$nilai_dpa)/2;
            return [
                'No' => $key + 1, 
                'NIM' => $nim, 
                'nama' => $nama, 
                'Perguruan Tinggi' => $nama_lemb, 
                'Prodi' => $prodi,
                'Free Form' => $item->freeform,
                'Nilai DPL' => $item->nilai_dpl,
                'Nilai DPA' => $item->nilai_dpa,
                'Nilai Akhir' => $nilai_akhir,
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
            'NIM',
            'Nama',
            'Perguruan Tinggi',
            'Prodi',
            'Free Form',
            'Nilai DPL',
            'Nilai DPA',
            'Nilai Akhir',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
