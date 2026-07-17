<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Mahasiswa_lokasi;

class UserGuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            // Kalo mahasiswa belum punya mahasiswa lokasi, alihkan ke halaman profile dan tidak bisa akses menu lain
            $desaExists = Mahasiswa_lokasi::where('id_mahasiswa', auth()->user()->mahasiswa?->id_mahasiswa)->exists();
            if (in_array(auth()->user()->role, ['mahasiswa']) && !$desaExists) {
                return redirect(url('mhsprofile'));
            }
        }

        return $next($request);
    }
}
