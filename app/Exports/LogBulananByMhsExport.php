<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use App\Models\Logbulanan;

class LogBulananByMhsExport implements FromCollection, WithHeadings
{
    protected $emailMahasiswa;
    public function __construct($emailMahasiswa)
    {
        $this->emailMahasiswa = $emailMahasiswa;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        //$logbulanan = Logbulanan::all();
        $logbulanan = Logbulanan::where('email', $this->emailMahasiswa)->get();
        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $logbulanan->map(function ($item, $key) {
            $deskripsi = $item->deskripsi ?? '-';
            if ($deskripsi !== '-') {
                $deskripsi = str_replace(
                    ['<br>', '<br/>', '<br />', '</p>', '</li>'],
                    "\n",
                    $deskripsi
                );

                $deskripsi = strip_tags($deskripsi);
                $deskripsi = html_entity_decode($deskripsi, ENT_QUOTES | ENT_HTML5);

                // Rapikan spasi tanpa menghapus newline
                $deskripsi = preg_replace('/[ \t]+/', ' ', $deskripsi);

                // Hilangkan baris kosong berlebih
                $deskripsi = preg_replace("/\n{3,}/", "\n\n", trim($deskripsi));
            }
            
            return [
                'No' => $key + 1, 
                'NIM' => $item->mahasiswa->nim, 
                'Nama Mahasiswa' => $item->mahasiswa->nama, 
                'Nama Perguruan Tinggi' => $item->mahasiswa->sp->nm_lemb, 
                'Bulan' => $item->bulan,
                'Deskripsi' => $deskripsi,
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
            'NIM',
            'Nama Mahasiswa',
            'Nama Perguruan Tinggi',
            'Bulan',
            'Deskripsi',
            'Nilai',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
