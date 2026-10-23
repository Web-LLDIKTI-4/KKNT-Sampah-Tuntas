<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Database cache store never purges expired rows (old version keys) on its own
Schedule::call(fn () => DB::table(config('cache.stores.database.table'))->where('expiration', '<', now()->timestamp)->delete())
    ->name('cache:prune-expired')
    ->description('Hapus baris cache database yang sudah kedaluwarsa')
    ->daily()
    ->when(fn () => config('cache.default') === 'database')
    ->withoutOverlapping();
