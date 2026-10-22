<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mahasiswa/DPL hanya boleh mengakses beranda & profil sampai PT-nya mengisi pelaksanaan KKN.
 */
class RequirePelaksanaanKkn
{
    public const MESSAGE = 'Perguruan Tinggi Anda belum mengisi waktu pelaksanaan KKN. Untuk sementara Anda hanya dapat melihat dan mengubah profil.';

    public const ALLOWED_PATHS = [
        'home', 'home/*',
        'profile', 'profile/*',
        'mhsprofile', 'mhsprofile/*',
        'setting', 'setting/*',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || ! in_array('auth', $request->route()?->gatherMiddleware() ?? [], true)
            || $request->is(...self::ALLOWED_PATHS)
            || ! $user->isLockedByPelaksanaan()) {
            return $next($request);
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => false, 'message' => self::MESSAGE], 403);
        }

        return redirect()->route('home')->with('error', self::MESSAGE);
    }
}
