<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role kepala hanya memantau: semua request yang mengubah data ditolak,
 * kecuali logout dan pengelolaan profil sendiri.
 */
class KepalaReadOnly
{
    private const ALLOWED = ['logout', 'profile/update', 'profile/prosesuploadpoto', 'setting/update'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === 'kepala'
            && ! $request->isMethodSafe()
            && ! in_array($request->path(), self::ALLOWED, true)) {
            abort(403, 'Akun kepala hanya dapat melihat data.');
        }

        return $next($request);
    }
}
