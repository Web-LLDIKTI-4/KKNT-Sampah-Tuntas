<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $role
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Verifikasi apakah peran pengguna ada di antara peran yang diizinkan
        foreach ($roles as $role) {
            if ($request->user()->role == $role) {
                return $next($request);
            }
        }

        // Jika peran pengguna tidak diizinkan, arahkan kembali ke halaman tertentu
        return redirect()->route('home'); // Ganti 'home' dengan nama rute yang diinginkan
    }
}
