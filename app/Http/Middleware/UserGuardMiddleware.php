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
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user->role === 'mahasiswa') {

            $mahasiswa = $user->mahasiswa;

            // Jika data mahasiswa belum ada
            if (! $mahasiswa) {
                return redirect()->route('mhsprofile');
            }

            // Jika belum memilih lokasi dan bukan sedang di halaman profile
            $hasLokasi = Mahasiswa_lokasi::where('id_mahasiswa', $mahasiswa->id_mahasiswa)->exists();

            if (! $hasLokasi && ! $request->routeIs('mhsprofile')) {
                return redirect()->route('mhsprofile');
            }
        }

        return $next($request);
    }
}
