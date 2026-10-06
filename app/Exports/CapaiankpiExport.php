<?php

namespace App\Exports;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\Kpicapaian;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

// Binder anti-formula: permasalahan/solusi/kendala teks bebas dari ketua
class CapaiankpiExport extends SafeValueBinder implements WithCustomValueBinder, FromCollection, WithHeadings
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
                ->get();
        } elseif (auth()->user()->role === 'pt') {
            $kpicapaian = Kpicapaian::whereIn('email', \App\Models\Mahasiswa::visibleTo(auth()->user())->select('email'))->get();
        } elseif ($this->emailMahasiswa) {
            // Scope sama dengan listdataserver (ketua/anggota kelompok)
            $kpicapaian = Kpicapaian::visibleToMahasiswa($this->emailMahasiswa)->get();
        } else {
            $kpicapaian = Kpicapaian::all();

        }

        $kpicapaian->load(['kpi', 'pjdesa.desa.kecamatan', 'pjdesa.mahasiswa.user.locationProgram']);

        // Lakukan relasi yang diperlukan dan tambahkan judul kolom
        $data = $kpicapaian->map(function ($item, $key) {
            $lokasi = $item?->pjdesa?->mahasiswa?->user?->locationProgram->nama_lokasi.', '.$item?->pjdesa?->desa?->kecamatan?->kecamatan.', '.$item?->pjdesa?->desa?->desa;
            $desa = $item?->pjdesa?->desa?->desa ?? '';
            $pjdesa = $item?->email ?? '';
            $kpi = $item->kpi ? $item->kpi->nama_kpi : null;

            return [
                'No' => $key + 1,
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
            'No',
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
}
