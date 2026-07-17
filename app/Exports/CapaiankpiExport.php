<?php

namespace App\Exports;

use App\Models\Kpicapaian;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CapaiankpiExport implements FromCollection, WithHeadings
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
                return 'Sudah Selesai';
            case 'P':
                return 'Proses';
            default:
                return 'Belum Ditindaklanjuti';
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
                ->get();
        } elseif ($this->emailMahasiswa) {
            $kpicapaian = Kpicapaian::where('email', $this->emailMahasiswa)->get();
        } else {
            $kpicapaian = Kpicapaian::all();

        }

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $kpicapaian->map(function ($item, $key) {
            $lokasi = $item?->pjdesa?->mahasiswa?->user?->locationProgram->nama_lokasi.', '.$item?->pjdesa?->desa?->kecamatan?->kecamatan.', '.$item?->pjdesa?->desa?->desa;
            $desa = $item?->pjdesa?->desa?->desa ?? '';
            $pjdesa = $item?->email ?? '';
            $kpi = $item->kpi ? $item->kpi->nama_kpi : null;
            $tahapan = $item->target ? $item->target->tahapan : null;
            $target_kpi = $item->target ? $item->target->nama_kpitarget : null;

            return [
                'No' => $key + 1,
                'Lokasi Kegiatan' => $lokasi,
                'PJ Desa' => $pjdesa,
                'KPI' => $kpi,
                'Tahapan' => $tahapan,
                'Target KPI' => $target_kpi,
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
            'No',
            'Lokasi Kegiatan',
            'PJ Desa',
            'Desa',
            'KPI',
            'Tahapan',
            'Target KPI',
            'Permasalahan',
            'Solusi',
            'Kebutuhan Dukungan',
            'Tindak Lanjut',
            'Tautan',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
