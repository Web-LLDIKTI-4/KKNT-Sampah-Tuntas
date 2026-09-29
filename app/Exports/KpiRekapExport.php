<?php

namespace App\Exports;

use App\Exports\Sheets\ArraySheet;
use App\Models\Kpicapaian;
use App\Services\KpiRekapService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Rekap KPI keseluruhan (mengikuti filter dasbor): per KPI, per kegiatan, per PT, dan isian ketua kelompok.
 */
class KpiRekapExport implements WithMultipleSheets
{
    public function __construct(private KpiRekapService $rekap, private array $filter) {}

    public function sheets(): array
    {
        return [
            new ArraySheet('Per KPI', ['KPI', 'Jumlah Kegiatan', 'Kegiatan Berdata', 'Rata-rata Capaian (%)'],
                $this->rekap->rekapPerKpi($this->filter)->map(fn ($r) => [
                    $r->nama_kpi, $r->jumlah_kegiatan, $r->kegiatan_berdata, $this->bulat($r->capaian),
                ])),

            new ArraySheet('Per Kegiatan', ['KPI', 'Kegiatan', 'Target', 'Satuan', 'Rata-rata Realisasi', 'Kelompok Selesai', 'Jumlah Kelompok', 'Capaian (%)'],
                $this->rekap->rekapPerKegiatan($this->filter)->map(fn ($r) => [
                    $r->nama_kpi, $r->kegiatan, $this->bulat($r->target), $r->satuan, $this->bulat($r->realisasi),
                    $r->jumlah_selesai, $r->jumlah_kelompok, $this->bulat($r->capaian),
                ])),

            new ArraySheet('Per Perguruan Tinggi', ['Perguruan Tinggi', 'Mahasiswa', 'KPI', 'Kegiatan', 'Target', 'Satuan', 'Rata-rata Realisasi', 'Kelompok Selesai', 'Jumlah Kelompok', 'Capaian (%)'],
                $this->rekap->rekapPerPt($this->filter)->map(fn ($r) => [
                    $r->nama_pt, $r->jumlah_mahasiswa, $r->nama_kpi, $r->kegiatan, $this->bulat($r->target), $r->satuan,
                    $this->bulat($r->realisasi), $r->jumlah_selesai, $r->jumlah_kelompok, $this->bulat($r->capaian),
                ])),

            new ArraySheet('Isian Ketua Kelompok', ['Ketua Kelompok', 'Email', 'Lokasi Program', 'Kecamatan', 'Kelurahan', 'KPI', 'Kegiatan', 'Target', 'Satuan', 'Realisasi', 'Tindak Lanjut', 'Capaian (%)'],
                $this->rekap->isianKelompok($this->filter)->map(fn ($r) => [
                    $r->nama, $r->email, $r->nama_lokasi, $r->kecamatan, $r->desa, $r->nama_kpi, $r->kegiatan,
                    $this->bulat($r->target), $r->satuan, $this->bulat($r->realisasi),
                    $r->id_capaian ? (Kpicapaian::STATUS[$r->status_capaian] ?? $r->status_capaian) : 'Belum diisi',
                    $this->bulat($r->capaian),
                ])),
        ];
    }

    private function bulat($value): ?float
    {
        return $value === null ? null : round((float) $value);
    }
}
