<?php

namespace App\Exports;

use App\Models\Logkegiatan;
use App\Models\Mahasiswa;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Auth;
use DB;
class LogkegiatanExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        $logkegiatan = Logkegiatan::get();
        
        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $logkegiatan->map(function ($item, $key) {
            return [
                'No' => $key + 1, 
                'Nama Mahasiswa' => $item->mahasiswa->nama, 
                'NIM' => strval($item->mahasiswa->nim),
                'Kode Perguruan Tinggi' => $item->mahasiswa->kodept, 
                'Jumlah Hari' => Logkegiatan::where('email', $item->email)
                ->select(DB::raw('count(distinct tanggal) as count'))
                ->value('count'),
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
            'Kode Perguruan Tinggi',
            'Jumlah Hari',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
