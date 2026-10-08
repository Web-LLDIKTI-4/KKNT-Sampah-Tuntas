<?php

namespace App\Exports;

use App\Models\Kehadiran;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LogkehadiranExport implements FromCollection, WithHeadings, WithMapping
{
    protected $email;
    private $index = 0;
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
        if ($this->email) {
            $kehadiran = Kehadiran::where('email', $this->email)->orderBy('tanggal')->get();
        } else {
            $kehadiran = Kehadiran::orderBy('tanggal')->get();

        }
        
        return $kehadiran;
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
            'Tanggal',
            'Status Kehadiran',
            'Jam Masuk',
            'Jam Pulang',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }

    /**
     * Memetakan data untuk setiap baris
     *
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->index++; // Increment index setiap kali map dipanggil
        return [
            'No' => $this->index,
            'Tanggal' => \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
            'Status Kehadiran' => ucfirst((string) $item->status_kehadiran),
            // Izin/kuliah tanpa jam: jangan parse null (jadi jam sekarang)
            'Jam Masuk' => $item->waktu_masuk ? \Carbon\Carbon::parse($item->waktu_masuk)->format('H:i:s') : '-',
            'Jam Pulang' => $item->waktu_pulang ? \Carbon\Carbon::parse($item->waktu_pulang)->format('H:i:s') : '-',
            // Tambahkan kolom lain sesuai kebutuhan
        ];
    }
}
