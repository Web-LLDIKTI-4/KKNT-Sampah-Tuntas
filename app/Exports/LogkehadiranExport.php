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
        return Kehadiran::where('email', $this->email)->get();
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
            'Tanggal' => $item->tanggal,
            'Status Kehadiran' => $item->status_kehadiran,
            'Jam Masuk' => $item->waktu_masuk,
            'Jam Pulang' => $item->waktu_pulang,
            // Tambahkan kolom lain sesuai kebutuhan
        ];
    }
}
