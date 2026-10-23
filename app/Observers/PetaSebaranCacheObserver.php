<?php

namespace App\Observers;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Services\PetaSebaranService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

// Invalidate public peta/capaian/sebaran cache when their source rows change
class PetaSebaranCacheObserver implements ShouldHandleEventsAfterCommit
{
    // Columns feeding the cached public data; unlisted models: any column
    private const COLUMNS = [
        Pjdesa::class => ['email', 'id_desa'],
        Dpl::class => ['kodept', 'location_program'],
        Mahasiswa::class => ['email', 'kodept', 'location_program'],
        Mahasiswa_lokasi::class => ['id_mahasiswa', 'id_desa', 'tahun'],
    ];

    // Shown publicly only for ketua kelompok (capaian)
    private const KETUA_COLUMNS = ['nama', 'phone'];

    public function saved(Model $model): void
    {
        // saved also fires on no-op saves; wasChanged(null) would check a null key
        $columns = self::COLUMNS[$model::class] ?? [];
        if ($model->wasRecentlyCreated || ($columns ? $model->wasChanged($columns) : $model->wasChanged())) {
            PetaSebaranService::flushCache();
        } elseif ($model instanceof Mahasiswa && $model->wasChanged(self::KETUA_COLUMNS) && Pjdesa::where('email', $model->email)->exists()) {
            PetaSebaranService::flushCache();
        }
    }

    public function deleted(Model $model): void
    {
        PetaSebaranService::flushCache();
    }
}
