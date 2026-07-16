<?php

namespace App\Exports;

use App\Models\Tugasakhir;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaptugasakhirExport implements FromCollection, WithHeadings
{
     /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        if (auth()->user()->role === 'dpl') {
            $tugasakhir = Tugasakhir::with(['dplmentoring'])
                ->whereHas('dplmentoring', function ($q) {
                    $q->where('email_dpl', auth()->user()->email);
                })
                ->get();
        } else {
            $tugasakhir = Tugasakhir::all();

        }

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $tugasakhir->map(function ($item, $key) {
            $nama_lemb = $item->mahasiswa ? $item->mahasiswa->sp->nm_lemb : null;
            $nama = $item->mahasiswa ? $item->mahasiswa->nama : null;
            $nim = $item->mahasiswa ? $item->mahasiswa->nim : null;

            return [
                'No' => $key + 1, 
                'Nim' => $nim,
                'Nama' => $nama,
                'Perguruan Tinggi' => $nama_lemb,
                'Tautan' => $item->tautan,
                'Nilai' => $item->nilai_dpl,
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
        return [
            'No',
            'Nim',
            'Nama',
            'Perguruan Tinggi',
            'Tautan',
            'Nilai',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
