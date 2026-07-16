<?php

namespace App\Exports;

use App\Models\Logkegiatan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Auth;

class LogharianmhsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data log bulanan
        $logkegiatan = Logkegiatan::with(['mahasiswa', 'dplmentoring'])
                    ->whereHas('dplmentoring', function ($query) {
                        $query->where('email_dpl', Auth::user()->email);
                    })
                    ->get();
        
        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $logkegiatan->map(function ($item, $key) {
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
                'Nama Mahasiswa' => $item->mahasiswa->nama, 
                'NIM' => $item->mahasiswa->nim,
                'Perguruan Tinggi' => $item->mahasiswa->sp->nm_lemb, 
                'Tanggal' => \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
                'Deskripsi' => $deskripsi,
                'KPI' => $item->kpi->nama_kpi,
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
            'Tanggal',
            'Deskripsi',
            'KPI',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
