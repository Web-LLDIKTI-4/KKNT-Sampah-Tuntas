<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Models\Panduan;

class PanduanPublikController extends Controller
{
    // Nonaktif, tidak ada, atau file hilang sengaja menghasilkan 404 yang sama
    // agar tamu tidak bisa membedakan panduan draf dari ID yang tidak ada
    public function unduh(string $id_panduan): StreamedResponse
    {
        $panduan = Panduan::where('is_aktif', true)->findOrFail($id_panduan);
        abort_unless(Storage::disk(Panduan::DISK)->exists($panduan->file_path), 404);

        return Storage::disk(Panduan::DISK)->download($panduan->file_path, $panduan->nama_file, [
            'Cache-Control' => 'no-store',
        ]);
    }
}
