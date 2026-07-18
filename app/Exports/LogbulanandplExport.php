<?php

namespace App\Exports;

use App\Models\Dpllaporan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LogbulanandplExport implements FromCollection, WithHeadings
{
    protected $email;
    public function __construct(String $email)
    {
        $this->email = $email;
    }

    function bulanToNama($bulan)
    {
        $namaBulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $namaBulan[$bulan] ?? '';
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        $logbulanan = Dpllaporan::where('email', $this->email)->orderBy('created_at', 'desc')->get();

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $logbulanan->map(function ($item, $key) {
            $nama_lemb = $item->dpl ? $item->dpl->sp->nm_lemb : null;
            $nama = $item->dpl ? $item->dpl->nama : null;
            $nidn = $item->dpl ? $item->dpl->nidn : null;

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
                'Nama DPL' => $nama, 
                'NIDN' => $nidn, 
                'Perguruan Tinggi' => $nama_lemb, 
                'Tahun' => $item->tahun,
                'Bulan' => $this->bulanToNama($item->bulan),
                'Deskripsi' => $deskripsi,
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
            'Nama DPL',
            'NIDN',
            'Perguruan Tinggi',
            'Tahun',
            'Bulan',
            'Deskripsi',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
