<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Panduan;

class PanduanDownload
{
    // Serahkan transfer ke nginx (X-Accel-Redirect) bila prefix diset, selain itu stream via PHP
    public static function response(Panduan $panduan, array $headers = []): Response
    {
        $prefix = (string) config('filesystems.panduan_x_accel_prefix');

        if ($prefix === '') {
            return Storage::disk(Panduan::DISK)->download($panduan->file_path, $panduan->nama_file, $headers);
        }

        $path = implode('/', array_map('rawurlencode', explode('/', ltrim($panduan->file_path, '/'))));
        $fallback = str_replace(['%', '/', '\\'], '', Str::ascii($panduan->nama_file));

        return response('', 200, [
            'X-Accel-Redirect' => rtrim($prefix, '/').'/'.$path,
            'Content-Type' => $panduan->mime ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $panduan->nama_file, $fallback),
            'X-Content-Type-Options' => 'nosniff',
        ] + $headers);
    }
}
