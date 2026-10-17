<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// Invalidate login header stats when mahasiswa/dpl/kecamatan/desa change
class LoginStatistikCacheObserver
{
    public const CACHE_KEY = 'login.statistik.v2';

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function created(Model $model): void
    {
        self::flush();
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged('kodept')) {
            self::flush();
        }
    }

    public function deleted(Model $model): void
    {
        self::flush();
    }
}
