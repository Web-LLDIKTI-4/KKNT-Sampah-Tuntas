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
        } elseif (auth()->user()->role === 'pt') {
            $kpicapaian = Kpicapaian::whereIn('email', \App\Models\Mahasiswa::visibleTo(auth()->user())->select('email'))->get();
        } elseif ($this->emailMahasiswa) {
            $kpicapaian = Kpicapaian::where('email', $this->emailMahasiswa)->get();
        } else {
            $kpicapaian = Kpicapaian::all();

        }

        $kpicapaian->load(['kpi', 'target', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram']);

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $kpicapaian->map(function ($item, $key) {
            $lokasi = $item?->pjdesa?->mahasiswa?->user?->locationProgram->nama_lokasi.', '.$item?->pjdesa?->desa?->kecamatan?->kecamatan.', '.$item?->pjdesa?->desa?->desa;
            $desa = $item?->pjdesa?->desa?->desa ?? '';
            $pjdesa = $item?->email ?? '';
            $kpi = $item->kpi ? $item->kpi->nama_kpi : null;
            $kegiatan = $item->target?->kegiatan;

            return [
                'No' => $key + 1,
                'Lokasi Kegiatan' => $lokasi,
                'PJ Desa' => $pjdesa,
                'Desa' => $desa,
                'KPI' => $kpi,
                'Kegiatan' => $kegiatan,
                'Target' => $item->target?->target,
                'Satuan Target' => $item->target?->satuan,
                'Realisasi' => $item->realisasi,
                'Satuan Realisasi' => $item->satuan,
                'Capaian (%)' => $item->capaianPersen(),
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
            'Kegiatan',
            'Target',
            'Satuan',
            'Realisasi',
            'Satuan',
            'Capaian (%)',
            'Permasalahan',
            'Solusi',
            'Kebutuhan Dukungan',
            'Tindak Lanjut',
            'Tautan',
            // Tambahkan judul kolom lain sesuai kebutuhan
        ];
    }
}
