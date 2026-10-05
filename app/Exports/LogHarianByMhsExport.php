<?php

namespace App\Exports;

use App\Models\Logkegiatan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Auth;

class LogHarianByMhsExport implements FromCollection, WithHeadings
{
    protected $emailMahasiswa;
    public function __construct($email, private bool $showIdentitas = true)
    {
        $this->emailMahasiswa = $email;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Logkegiatan::where('email', $this->emailMahasiswa)
            ->with('mahasiswa.sp')
            ->without('dplmentoring')
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item, $key) => [
                $key + 1,
                $item->mahasiswa?->nama,
                (string) $item->mahasiswa?->nim,
                $item->mahasiswa?->sp?->nm_lemb,
                \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
                $this->showIdentitas ? $item->nama_kepala_keluarga : 'tidak ditampilkan',
                $this->showIdentitas ? $item->alamat_rumah : 'tidak ditampilkan',
                $item->rt,
                $item->rw,
                $item->memilah ? 'Ya' : 'Tidak',
                (float) $item->organik_kg,
                (float) $item->anorganik_kg,
                (float) $item->residu_kg,
            ]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Mahasiswa',
            'NIM',
            'Perguruan Tinggi',
            'Tanggal',
            'Nama Kepala Keluarga',
            'Alamat Rumah',
            'RT',
            'RW',
            'Sudah Memilah',
            'Organik Terkelola (Kg)',
            'Anorganik Terkelola (Kg)',
            'Residu (Kg)',
        ];
    }
}
