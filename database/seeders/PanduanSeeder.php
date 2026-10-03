<?php

namespace Database\Seeders;

use App\Models\Panduan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 100 panduan dummy, masing-masing punya file PDF mini sendiri di disk local (panduan/dummy/).
 * Aman dijalankan ulang: baris & file dummy lama dihapus dulu, panduan asli (di luar panduan/dummy/) tidak disentuh.
 * Jalankan: php artisan db:seed --class=PanduanSeeder
 */
class PanduanSeeder extends Seeder
{
    private const JUMLAH = 100;

    private const FOLDER_DUMMY = 'panduan/dummy';

    public function run(): void
    {
        $disk = Storage::disk(Panduan::DISK);

        // Bersihkan hasil seed sebelumnya (query delete: file sudah dihapus lewat deleteDirectory)
        Panduan::query()->where('file_path', 'like', self::FOLDER_DUMMY.'/%')->delete();
        $disk->deleteDirectory(self::FOLDER_DUMMY);

        $adminId = User::where('role', 'admin')->value('id');

        for ($i = 0; $i < self::JUMLAH; $i++) {
            $panduan = Panduan::factory()->make(['uploaded_by' => $adminId]);

            $isiPdf = $this->pdfMini($panduan->judul);
            $path = self::FOLDER_DUMMY.'/'.Str::uuid().'.pdf';
            $disk->put($path, $isiPdf);

            $panduan->file_path = $path;
            $panduan->ukuran = strlen($isiPdf);
            $panduan->save();
        }

        // Query delete di atas tidak memicu event model, jadi cache login dihapus manual
        Cache::forget(Panduan::PUBLIC_CACHE_KEY);

        $this->command?->info('PanduanSeeder: '.self::JUMLAH.' panduan dummy + file PDF dibuat.');
    }

    // PDF 1 halaman valid (dengan xref offset yang benar) berisi judul, tanpa dependency tambahan
    private function pdfMini(string $judul): string
    {
        $teks = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii($judul));
        $stream = "BT /F1 16 Tf 50 780 Td ({$teks}) Tj ET";

        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $n => $isi) {
            $offset[] = strlen($pdf);
            $pdf .= ($n + 1)." 0 obj\n{$isi}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objek) + 1)."\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= 'trailer
<< /Size '.(count($objek) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
