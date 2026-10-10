<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role kepala & pemda hanya memantau: semua request yang mengubah data ditolak,
 * kecuali logout, pengelolaan profil sendiri, dan profil desa khusus pemda.
 */
class KepalaReadOnly
{
    private const ALLOWED = ['logout', 'profile/update', 'profile/prosesuploadpoto', 'setting/update'];

    private const PEMDA_ALLOWED = [
        'desaprofile/insert', 'desaprofile/update', 'desaprofile/destroy',
        'kecamatan/insert', 'kecamatan/update', 'kecamatan/destroy',
        'desa/insert', 'desa/update', 'desa/destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isPemantau()
            && ! $request->isMethodSafe()
            && ! in_array($request->path(), self::ALLOWED, true)
            && ! ($request->user()->role === 'pemda' && in_array($request->path(), self::PEMDA_ALLOWED, true))) {
            abort(403, 'Akun pemantau hanya dapat melihat data.');
        }

        return $next($request);
    }
}
