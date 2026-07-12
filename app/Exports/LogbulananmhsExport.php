<?php

namespace App\Exports;

use App\Models\Logbulanan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Auth;

class LogbulananmhsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        //$logbulanan = Logbulanan::all();
        if (Auth::check() && Auth::user()->role == 'dpl') {
            $logbulanan = Logbulanan::with(['mahasiswa', 'dplmentoring'])
                ->whereHas('dplmentoring', function ($query) {
                    $query->where('email_dpl', Auth::user()->email);
                })
                ->get();
        } else {
            $logbulanan = Logbulanan::with(['mahasiswa'])->get();
        }                      
        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $logbulanan->map(function ($item, $key) {
            return [
                'No' => $key + 1, 
                'Nama Mahasiswa' => $item->mahasiswa->nama, 
                'NIM' => $item->mahasiswa->nim, 
                'Perguruan Tinggi' => $item->mahasiswa->sp->nm_lemb, 
                'Bulan' => $item->bulan,
                'Deskripsi' => $item->deskripsi,
                'Nilai' => $item->nilai,
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
            'Nama Mahasiswa',
            'NIM',
            'Perguruan Tinggi',
            'Bulan',
            'Deskripsi',
            'Nilai',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
