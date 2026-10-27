<?php

namespace App\Policies;

use App\Models\RealisasiKerja;
use App\Models\User;

class RealisasiKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, RealisasiKerja::VIEWER_ROLES, true);
    }

    // Konsisten dengan RealisasiKerja::scopeVisibleTo
    public function view(User $user, RealisasiKerja $realisasiKerja): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if (in_array($user->role, RealisasiKerja::ALL_PT_ROLES, true)) {
            return true;
        }

        $kodept = $user->ptKodept();

        return $kodept !== null && $realisasiKerja->kodept === $kodept;
    }

    public function create(User $user): bool
    {
        return $user->role === 'pt' && $user->pt()->exists();
    }

    public function update(User $user, RealisasiKerja $realisasiKerja): bool
    {
        return $user->role === 'admin' || $this->isOwner($user, $realisasiKerja);
    }

    public function delete(User $user, RealisasiKerja $realisasiKerja): bool
    {
        return $this->update($user, $realisasiKerja);
    }

    private function isOwner(User $user, RealisasiKerja $realisasiKerja): bool
    {
        return $user->role === 'pt' && $realisasiKerja->kodept === $user->email;
    }
}
