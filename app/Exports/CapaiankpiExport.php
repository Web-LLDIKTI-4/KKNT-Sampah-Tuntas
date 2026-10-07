<?php

namespace App\Exports;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\Kpicapaian;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Binder anti-formula: permasalahan/solusi/kendala teks bebas dari ketua
class CapaiankpiExport extends SafeValueBinder implements WithCustomValueBinder, FromCollection, WithHeadings, WithEvents, WithStrictNullComparison
{
    protected $emailMahasiswa;

    public function __construct($emailMahasiswa = null)
    {
        $this->emailMahasiswa = $emailMahasiswa;
    }

    public function statusFormat($status)
    {
        switch ($status) {
            case 'Y':
                return 'Sudah';
            case 'P':
                return 'Proses';
            default:
                return 'Belum';
        }
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Ambil data log bulanan
        if (auth()->user()->role === 'dpl') {
            $kpicapaian = Kpicapaian::with(['dplMentoring'])
                ->whereHas('dplMentoring', function ($q) {
                    $q->where('email_dpl', auth()->user()->email);
                })
                ->orderByDesc('bulan')->get();
        } elseif (auth()->user()->role === 'pt') {
            $kpicapaian = Kpicapaian::whereIn('email', \App\Models\Mahasiswa::visibleTo(auth()->user())->select('email'))->orderByDesc('bulan')->get();
        } elseif ($this->emailMahasiswa) {
            // Scope sama dengan listdataserver (ketua/anggota kelompok)
            $kpicapaian = Kpicapaian::visibleToMahasiswa($this->emailMahasiswa)->orderByDesc('bulan')->get();
        } else {
            $kpicapaian = Kpicapaian::orderByDesc('bulan')->get();

        }

        $kpicapaian->load(['kpi', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram']);

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $kpicapaian->map(function ($item) {
            $lokasi = $item?->pjdesa?->mahasiswa?->user?->locationProgram->nama_lokasi.', '.$item?->pjdesa?->desa?->kecamatan?->kecamatan.', '.$item?->pjdesa?->desa?->desa;
            $desa = $item?->pjdesa?->desa?->desa ?? '';
            $pjdesa = $item?->email ?? '';
            $kpi = $item->kpi ? $item->kpi->nama_kpi : null;

            return [
                'Bulan' => $item->bulan ? \Illuminate\Support\Carbon::parse($item->bulan)->translatedFormat('F Y') : '-',
                'Lokasi Kegiatan' => $lokasi,
                'PJ Desa' => $pjdesa,
                'Desa' => $desa,
                'KPI' => $kpi,
                'Permasalahan' => $item->permasalahan,
                'Solusi' => $item->solusi,
                'Kebutuhan Dukungan' => $item->kendala,
                'Tindak Lanjut' => $this->statusFormat($item->status_capaian),
                'Tautan' => $item->tautan,
                // Tambahkan kolom lain sesuai kebutuhan
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        // Tentukan judul kolom
        return [
            'Bulan',
            'Lokasi Kegiatan',
            'PJ Desa',
            'Desa',
            'KPI',
            'Permasalahan',
            'Solusi',
            'Kebutuhan Dukungan',
            'Tindak Lanjut',
            'Tautan',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $akhir = $sheet->getHighestRow();

            $sheet->getStyle('A1:J1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A1:J$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A1:J$akhir")->getAlignment()->setWrapText(true);
            if ($akhir >= 2) {
                $sheet->getStyle("A2:J$akhir")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            }

            $lebar = ['A' => 14, 'B' => 30, 'C' => 28, 'D' => 20, 'E' => 30, 'F' => 40, 'G' => 40, 'H' => 40, 'I' => 14, 'J' => 30];
            foreach ($lebar as $kolom => $w) {
                $sheet->getColumnDimension($kolom)->setWidth($w);
            }
            $sheet->freezePane('A2');
        }];
    }
}
