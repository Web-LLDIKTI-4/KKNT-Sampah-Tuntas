<?php

namespace App\Policies;

use App\Models\RencanaKerja;
use App\Models\User;

class RencanaKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, RencanaKerja::VIEWER_ROLES, true);
    }

    public function view(User $user, RencanaKerja $rencanaKerja): bool
    {
        if ($user->role === 'pt') {
            return $this->isOwner($user, $rencanaKerja);
        }

        return $this->viewAny($user);
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
