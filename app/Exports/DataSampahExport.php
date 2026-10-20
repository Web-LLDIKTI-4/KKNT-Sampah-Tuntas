<?php

namespace App\Exports;

use App\Services\PenguranganSampahService;
use App\Support\XlsxStreamer;
use Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Data Sampah detail per log (stream, lazy chunk): judul Lokasi Program, baris log,
 * Subtotal Minggu (PEMDA), Subtotal Bulan, Total Lokasi. Subtotal dari dataSampahSubtotal() (cache).
 * Filter sudah di-scope controller (PT dikunci kodept).
 */
class DataSampahExport
{
    private array $info = [];

    // Memo nama bulan per 'Y-m-01'
    private array $namaBulan = [];

    public function __construct(
        private PenguranganSampahService $sampah,
        private array $filter,
        private bool $mingguan,
        private Collection $subtotal,
    ) {}

    public function info(array $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function download(string $filename): StreamedResponse
    {
        return $this->streamer()->download($filename, $this->rows());
    }

    public function writeTo(string $path): void
    {
        $this->streamer()->writeTo($path, $this->rows());
    }

    // Baris sudah urut lokasi → bulan → minggu (dataSampahQuery), subtotal disisipkan saat grup berganti
    private function rows(): Generator
    {
        $perLokasi = $this->subtotal->keyBy(fn ($l) => (string) $l->id);
        $lokasi = $bulan = $minggu = null;
        $idLokasi = $kodeBulan = $noMinggu = null;

        foreach ($this->sampah->dataSampahQuery($this->filter)->lazy(1000) as $r) {
            if ($idLokasi === null || $idLokasi !== (string) $r->lokasi_id) {
                yield from $this->tutup($lokasi, $bulan, $minggu);
                $idLokasi = (string) $r->lokasi_id;
                $lokasi = $perLokasi[$idLokasi] ?? null;
                $bulan = $minggu = $kodeBulan = $noMinggu = null;
                yield XlsxStreamer::summary($this->judulLokasi($r));
            }
            if ($kodeBulan !== $r->bulan) {
                yield from $this->tutup(null, $bulan, $minggu);
                $kodeBulan = $r->bulan;
                $bulan = $lokasi?->bulan->firstWhere('bulan', substr($r->bulan, 0, 7));
                $minggu = $noMinggu = null;
            }
            if ($this->mingguan && $noMinggu !== (int) $r->minggu) {
                yield from $this->tutup(null, null, $minggu);
                $noMinggu = (int) $r->minggu;
                $minggu = $bulan?->minggu->firstWhere('minggu', $noMinggu);
            }

            yield $this->baris($r);
        }

        yield from $this->tutup($lokasi, $bulan, $minggu);
    }

    // Subtotal minggu → bulan → total lokasi yang sedang terbuka
    private function tutup(?object $lokasi, ?object $bulan, ?object $minggu): Generator
    {
        if ($minggu) {
            yield XlsxStreamer::summary('Subtotal '.$minggu->label, (array) $minggu->subtotal);
        }
        if ($bulan) {
            yield XlsxStreamer::summary('Subtotal '.$bulan->nama_bulan, (array) $bulan->subtotal);
        }
        if ($lokasi) {
            yield XlsxStreamer::summary('Total '.$lokasi->nama, (array) $lokasi->total);
        }
    }

    private function judulLokasi(object $r): string
    {
        $koef = $r->koefisien === null ? '-' : rtrim(rtrim(number_format((float) $r->koefisien, 4, ',', '.'), '0'), ',');

        return 'Lokasi Program: '.($r->nama_lokasi ?? 'Tanpa Lokasi Program').'  |  Koefisien Timbulan Sampah: '.$koef;
    }

    private function baris(object $r): array
    {
        return [
            'nama_bulan' => $this->namaBulan[$r->bulan] ??= Carbon::parse($r->bulan)->translatedFormat('F Y'),
            'minggu_label' => PenguranganSampahService::labelMinggu((int) $r->minggu, $r->bulan),
            'tanggal' => $r->tanggal,
            'kecamatan' => $r->kecamatan,
            'desa' => $r->desa,
            'nama_kepala_keluarga' => $r->nama_kepala_keluarga,
            'alamat_rumah' => $r->alamat_rumah,
        ] + PenguranganSampahService::barisLog($r, $r->koefisien === null ? null : (float) $r->koefisien);
    }

    private function streamer(): XlsxStreamer
    {
        $columns = array_map(fn ($k) => XlsxStreamer::column($k['label'], $k['data'], $k['width'], $k['format']), self::kolom($this->mingguan));

        return (new XlsxStreamer('Data Sampah', $columns))
            ->numbered(false)
            ->info($this->info);
    }

    /**
     * Satu sumber kolom Data Sampah (export & view _data_sampah). Label sesuai template; huruf rumus dihitung
     * dari posisi (PEMDA bergeser +2 karena Bulan & Minggu).
     *
     * @return array<int, array{data: string, label: string, huruf: string, tipe: string, format: ?string, width: float}>
     */
    public static function kolom(bool $mingguan): array
    {
        // [key, label, tipe tampilan (text|angka|berat|persen), lebar Excel]
        $defs = [
            ...($mingguan ? [['nama_bulan', 'Bulan', 'text', 16], ['minggu_label', 'Minggu', 'text', 16]] : []),
            ['tanggal', 'Tanggal', 'text', 12],
            ['kecamatan', 'Nama Kecamatan', 'text', 20],
            ['desa', 'Nama Desa/Kelurahan', 'text', 22],
            ['nama_kepala_keluarga', 'Nama Kepala Rumah', 'text', 26],
            ['alamat_rumah', 'Alamat Rumah', 'text', 32],
            ['jml_rumah', 'Jumlah Rumah Keseluruhan', 'angka', 14],
            ['jml_rumah_memilah', 'Jumlah Rumah yang memilah', 'angka', 14],
            ['persen_ketaatan', 'Persentase Ketaatan Pemilahan [({jml_rumah_memilah}/{jml_rumah})*100%]', 'persen', 16],
            ['organik', 'Jumlah Sampah Organik Terkelola (Kg)', 'berat', 14],
            ['anorganik', 'Jumlah Sampah Anorganik Terkelola (Kg)', 'berat', 14],
            ['residu', 'Jumlah Sampah Residu (Kg)', 'berat', 14],
            ['jml_jiwa', 'Jumlah Jiwa (Orang)', 'angka', 12],
            ['total_terkelola', 'Total Sampah Terkelola (Kg) [{organik}+{anorganik}]', 'berat', 16],
            ['total_dihasilkan', 'Total Sampah Dihasilkan (Kg) [{organik}+{anorganik}+{residu}]', 'berat', 16],
            ['persen_pengelolaan', 'Persentase Pengelolaan Sampah [({total_terkelola}/{total_dihasilkan})*100%]', 'persen', 16],
            ['persen_penurunan_timbulan', 'Persentase Penurunan Timbulan Sampah [(1-({total_dihasilkan}/(koefisien*{jml_jiwa})))*100%]', 'persen', 20],
        ];

        // Maksimal 26 kolom (A–Z)
        $huruf = [];
        foreach ($defs as $i => [$key]) {
            $huruf['{'.$key.'}'] = chr(65 + $i);
        }

        return array_map(fn ($d, $i) => [
            'data' => $d[0],
            'label' => strtr($d[1], $huruf),
            'huruf' => chr(65 + $i),
            'tipe' => $d[2],
            'format' => $d[0] === 'tanggal' ? 'date' : ['angka' => 'number', 'berat' => 'number', 'persen' => 'percent'][$d[2]] ?? null,
            'width' => $d[3],
        ], $defs, array_keys($defs));
    }
}
