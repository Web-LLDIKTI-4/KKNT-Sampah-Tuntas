<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data milik mahasiswa yang dikaitkan lewat kolom `email`.
 */
trait OwnedByEmail
{
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where($this->qualifyColumn('email'), $user->email);
    }
}
