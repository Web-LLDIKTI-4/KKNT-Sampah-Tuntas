<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\SafeValueBinder;
use App\Models\Kpicapaian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export mahasiswa: tabel Data Sampah (format RekapLldiktiSheet) lalu tabel Capaian KPI (kolom tabel UI); periode jadi kolom Bulan.
 */
class CapaianKpiPeriodeSheet extends SafeValueBinder implements WithCustomValueBinder, FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    // Sama dengan kolom tampil di kpicapaian/listdata
    public const HEADER_KPI = [
        'Bulan', 'Lokasi Kegiatan', 'Nama KPI', 'Permasalahan',
        'Solusi', 'Kebutuhan Dukungan', 'Tindak Lanjut', 'Tautan',
    ];

    // Nomor baris per jenis, diisi saat array() lalu di-style di AfterSheet
    private array $subJudul = [];

    private array $tabel = [];

    // Tabel sampah: [baris header, baris akhir] + merge Bulan/Kecamatan dari RekapLldiktiSheet
    private ?array $sampah = null;

    private array $merges = [];

    private array $kosong = [];

    /**
     * @param  Collection  $rekap  output KpiSampahService::rekapLldikti()
     * @param  Collection  $capaian  Kpicapaian dengan relasi kpi & pjdesa sudah di-load
     */
    public function __construct(private Collection $rekap, private Collection $capaian) {}

    public function title(): string
    {
        return 'Capaian KPI';
    }

    public function array(): array
    {
        $rows = [];
        $baris = 1;
        $tambah = function (array $row) use (&$rows, &$baris) {
            $rows[] = $row;

            return $baris++;
        };

        // Tabel atas: format RekapLldiktiSheet apa adanya, semua periode kontinu
        $this->subJudul[] = $tambah(['Data Sampah']);
        $awal = $tambah(RekapLldiktiSheet::HEADER);
        foreach ($this->rekap as $grup) {
            [$isi, $merges] = RekapLldiktiSheet::grupBulan($grup, $baris);
            array_walk($isi, fn ($row) => $tambah($row));
            array_push($this->merges, ...$merges);
        }
        if ($baris === $awal + 1) {
            $this->kosong[] = [$tambah(['Belum ada data']), 'L'];
        }
        $this->sampah = [$awal, $baris - 1];

        $tambah(['']); // pemisah; [] kosong dibuang Maatwebsite

        // Tabel bawah: kolom & urutan sama dengan tabel UI kpicapaian
        $this->subJudul[] = $tambah(['Capaian KPI']);
        $awal = $tambah(self::HEADER_KPI);
        foreach ($this->capaian->values() as $i => $item) {
            $tambah($this->barisKpi($i + 1, $item));
        }
        if ($this->capaian->isEmpty()) {
            $this->kosong[] = [$tambah(['Belum ada data']), 'I'];
        }
        $this->tabel[] = [$awal, $baris - 1, 'I'];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            // Format tabel sampah persis RekapLldiktiSheet
            RekapLldiktiSheet::lebarKolom($sheet);
            if ($this->sampah) {
                RekapLldiktiSheet::gaya($sheet, ...$this->sampah);
            }
            foreach ($this->merges as $range) {
                $sheet->mergeCells($range);
            }

            foreach ($this->subJudul as $r) {
                $sheet->getStyle("A$r")->getFont()->setBold(true);
            }

            foreach ($this->tabel as [$header, $akhir, $kolomAkhir]) {
                $sheet->getStyle("A$header:$kolomAkhir$header")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A$header:$kolomAkhir$akhir")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                // Kolom ikut lebar LLDIKTI → teks KPI di-wrap
                $sheet->getStyle("A$header:$kolomAkhir$akhir")->getAlignment()->setWrapText(true);
                if ($akhir > $header) {
                    $sheet->getStyle('A'.($header + 1).":$kolomAkhir$akhir")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                }
            }
            // Baris "Belum ada ..." di-merge selebar tabelnya
            foreach ($this->kosong as [$r, $kolomAkhir]) {
                $sheet->mergeCells("A$r:$kolomAkhir$r");
            }
        }];
    }

    private function barisKpi(int $no, object $item): array
    {
        // Lokasi seperti listdataserver: "lokasi" baris baru "kecamatan, desa"
        $nama = $item->pjdesa?->mahasiswa?->user?->locationProgram?->nama_lokasi;
        $desa = $item->pjdesa?->desa;
        $lokasi = $nama && $desa?->kecamatan ? "$nama\n{$desa->kecamatan->kecamatan}, {$desa->desa}" : 'Tidak Diketahui';

        return [
            $item->bulan ? Carbon::parse($item->bulan)->translatedFormat('F Y') : '-',
            $lokasi,
            $item->kpi?->nama_kpi ?? '',
            $item->permasalahan,
            $item->solusi,
            $item->kendala,
            Kpicapaian::STATUS[$item->status_capaian] ?? Kpicapaian::STATUS['N'],
            $item->tautan,
        ];
    }
}
