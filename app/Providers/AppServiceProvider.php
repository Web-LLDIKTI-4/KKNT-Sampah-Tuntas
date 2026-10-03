<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Form sensitif (ganti password, upload, import): per user
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip()));

        // Form publik tanpa login (saran): per IP
        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Unduhan panduan publik di halaman login: per IP, cegah penyedotan bandwidth
        RateLimiter::for('public-download', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
