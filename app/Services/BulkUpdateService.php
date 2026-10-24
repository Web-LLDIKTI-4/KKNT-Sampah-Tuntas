<?php

namespace App\Services;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\BulkResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Edit massal dalam satu transaksi (semua atau tidak sama sekali).
 * Mass update melewati PetaSebaranCacheObserver, jadi cache di-flush manual setelah commit.
 */
class BulkUpdateService
{
    // Roles whose single edit form has location_program
    private const LOKASI_ROLES = ['mahasiswa', 'dpl', 'pt'];

    private const NOT_FOUND = 'Data tidak ditemukan / sudah terhapus';

    public function updateUserLokasi(array $ids, string $lokasi): BulkResult
    {
        $result = new BulkResult;

        DB::transaction(function () use ($ids, $lokasi, $result) {
            $users = User::whereIn('id', $ids)->lockForUpdate()->get(['id', 'name', 'email', 'role'])->keyBy('id');
            $eligible = collect();
            foreach ($ids as $id) {
                $user = $users->get($id);
                if (! $user) {
                    $result->skip($id, $id, self::NOT_FOUND);
                } elseif (! in_array($user->role, self::LOKASI_ROLES, true)) {
                    $result->skip($id, $user->name, 'Peran '.ucfirst($user->role).' tidak memiliki lokasi program');
                } else {
                    $eligible->push($user);
                }
            }
            if ($eligible->isEmpty()) {
                return;
            }

            User::whereIn('id', $eligible->pluck('id'))->update(['location_program' => $lokasi]);
            // Keep person records in sync, same as UserController::updateuser
            foreach (['mahasiswa' => Mahasiswa::class, 'dpl' => Dpl::class] as $role => $model) {
                $emails = $eligible->where('role', $role)->pluck('email')->filter();
                if ($emails->isNotEmpty()) {
                    $model::whereIn('email', $emails)->update(['location_program' => $lokasi]);
                }
            }
            $result->processed($eligible->count());
        });

        if ($result->processedCount() > 0) {
            PetaSebaranService::flushCache();
        }

        return $result;
    }

    /**
     * @param  class-string<Model>  $model  Mahasiswa or Dpl
     * @param  string  $role  linked users.role
     */
    public function updatePersons(string $model, string $role, array $ids, array $fields): BulkResult
    {
        $result = new BulkResult;
        $key = (new $model)->getKeyName();

        DB::transaction(function () use ($model, $role, $ids, $fields, $key, $result) {
            $records = $model::whereIn($key, $ids)->lockForUpdate()->get([$key, 'email'])->keyBy($key);
            foreach ($ids as $id) {
                if (! $records->has($id)) {
                    $result->skip($id, $id, self::NOT_FOUND);
                }
            }
            if ($records->isEmpty()) {
                return;
            }

            $model::whereIn($key, $records->keys())->update($fields);
            // Login checks users.location_program; same sync as the single edit
            if (array_key_exists('location_program', $fields)) {
                $emails = $records->pluck('email')->filter();
                if ($emails->isNotEmpty()) {
                    User::where('role', $role)->whereIn('email', $emails)->update(['location_program' => $fields['location_program']]);
                }
            }
            $result->processed($records->count());
        });

        if ($result->processedCount() > 0 && array_intersect_key($fields, array_flip(['kodept', 'location_program']))) {
            PetaSebaranService::flushCache();
        }

        return $result;
    }
}
