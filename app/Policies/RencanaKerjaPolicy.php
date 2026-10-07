<?php

namespace App\Policies;

use App\Models\RencanaKerja;
use App\Models\User;

class RencanaKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'pt', 'kepala', 'pemda'], true);
    }

    public function view(User $user, RencanaKerja $rencanaKerja): bool
    {
        return $user->role === 'admin' || $user->isPemantau() || $this->isOwner($user, $rencanaKerja);
    }

    public function create(User $user): bool
    {
        return $user->role === 'pt' && $user->pt()->exists();
    }

    public function update(User $user, RencanaKerja $rencanaKerja): bool
    {
        return $user->role === 'admin' || $this->isOwner($user, $rencanaKerja);
    }

    public function delete(User $user, RencanaKerja $rencanaKerja): bool
    {
        return $this->update($user, $rencanaKerja);
    }

    private function isOwner(User $user, RencanaKerja $rencanaKerja): bool
    {
        return $user->role === 'pt' && $rencanaKerja->kodept === $user->email;
    }
}
