<?php

namespace Database\Seeders;

use App\Models\RencanaKerja;
use App\Models\User;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 1 rencana kerja tahun berjalan per PT simulasi, diunggah akun PT, beserta file PDF dummy di disk RencanaKerja::DISK.
 * Kunci: kodept + tahun + judul; file yang hilang dibuat ulang.
 * Jalankan: php artisan db:seed --class=RencanaKerjaSeeder
 * Prasyarat: PerguruanTinggiSeeder
 */
class RencanaKerjaSeeder extends Seeder
{
    use SeedsDummyData;

    private const FOLDER = 'rencana-kerja/dummy';

    public function run(): void
    {
        $pts = $this->perguruanTinggi();
        $akunPt = User::where('role', 'pt')->whereIn('email', $pts->pluck('npsn'))->pluck('id', 'email');
        $this->requireData($akunPt->count() === $pts->count(), 'PerguruanTinggiSeeder');
        $this->seedRandom();

        $disk = Storage::disk(RencanaKerja::DISK);
        $tahun = (int) now()->format('Y');

        foreach ($pts as $pt) {
            $judul = 'Rencana Kerja KKNT '.$tahun.' – '.$pt->nm_lemb;
            $isiPdf = $this->pdfMini($judul);

            $rencana = RencanaKerja::where(['kodept' => $pt->npsn, 'tahun' => $tahun, 'judul' => $judul])->first()
                ?? RencanaKerja::factory()->forPt($pt->npsn)->create([
                    'judul' => $judul,
                    'tahun' => $tahun,
                    'file_path' => self::FOLDER.'/'.Str::uuid().'.pdf',
                    'nama_file' => Str::slug($judul).'.pdf',
                    'ukuran' => strlen($isiPdf),
                    'uploaded_by' => $akunPt[$pt->npsn],
                ]);

            if (! $disk->exists($rencana->file_path)) {
                $disk->put($rencana->file_path, $isiPdf);
            }
        }
    }
}
