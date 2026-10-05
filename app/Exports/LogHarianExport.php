<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

use App\Models\Logkegiatan;

use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;

class LogHarianExport implements FromCollection, WithHeadings, WithStyles
{
    protected $email;
    private $index = 0;
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Logkegiatan::where('email', $this->email)
            ->without(['mahasiswa', 'dplmentoring'])
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item, $key) => [
                $key + 1,
                \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
                $item->nama_kepala_keluarga,
                $item->alamat_rumah,
                $item->rt,
                $item->rw,
                $item->memilah ? 'Ya' : 'Tidak',
                (float) $item->organik_kg,
                (float) $item->anorganik_kg,
                (float) $item->residu_kg,
            ]);
    }

    public function headings(): array
    {
        return [
            'No',
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

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);

        return [];
    }
}
