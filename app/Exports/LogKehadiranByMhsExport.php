<?php

namespace App\Exports;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\Kehadiran;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LogKehadiranByMhsExport extends SafeValueBinder implements WithCustomValueBinder, FromCollection, WithHeadings
{
    protected $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Mengambil koleksi data dari model Kehadiran
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Ambil data berdasarkan email jika diperlukan
        $kehadiran = Kehadiran::with(['mahasiswa.sp'])->where('email', $this->email)->orderBy('tanggal')->get();

        $data = $kehadiran->map(function ($item, $key) {
            return [
                'No' => $key + 1,
                'NIM' => $item->mahasiswa->nim ?? 'NIM tidak tersedia',
                'Nama Mahasiswa' => $item->mahasiswa->nama ?? 'Nama tidak tersedia',
                'Nama Perguruan Tinggi' => $item->mahasiswa->sp->nm_lemb ?? 'Perguruan Tinggi tidak tersedia',
                'Tanggal' => \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
                'Status Kehadiran' => ucfirst((string) $item->status_kehadiran),
                'Jam Masuk' => $item->waktu_masuk ? \Carbon\Carbon::parse($item->waktu_masuk)->format('H:i:s') : '-',
                'Jam Pulang' => $item->waktu_pulang ? \Carbon\Carbon::parse($item->waktu_pulang)->format('H:i:s') : '-',
            ];
        });

        return $data;
    }

    /**
     * Menentukan judul kolom pada Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'NIM',
            'Nama Mahasiswa',
            'Nama Perguruan Tinggi',
            'Tanggal',
            'Status Kehadiran',
            'Jam Masuk',
            'Jam Pulang',
        ];
    }

}
