<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Panduan;
use App\Support\PanduanDownload;

class PanduanPublikController extends Controller
{
    // Nonaktif, tidak ada, atau file hilang sengaja menghasilkan 404 yang sama
    // agar tamu tidak bisa membedakan panduan draf dari ID yang tidak ada
    public function unduh(string $id_panduan): Response
    {
        $panduan = Panduan::where('is_aktif', true)->findOrFail($id_panduan);
        abort_unless(Storage::disk(Panduan::DISK)->exists($panduan->file_path), 404);

        return PanduanDownload::response($panduan, [
            'Cache-Control' => 'no-store',
        ]);
    }
}
