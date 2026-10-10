<?php

namespace App\Observers;

use App\Services\PetaSebaranService;
use Illuminate\Database\Eloquent\Model;

// Invalidate public peta cache when desa/kecamatan change
class PetaSebaranCacheObserver
{
    public function saved(Model $model): void
    {
        PetaSebaranService::flushCache();
    }

    public function deleted(Model $model): void
    {
        PetaSebaranService::flushCache();
    }
}
