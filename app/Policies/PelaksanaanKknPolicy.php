<?php

namespace App\Policies;

use App\Models\PelaksanaanKkn;
use App\Models\User;

class PelaksanaanKknPolicy
{
    // Daftar semua PT read-only; admin juga tidak boleh mengubah
    public function viewAny(User $user): bool
    {
        return in_array($user->role, PelaksanaanKkn::VIEWER_ROLES, true);
    }

    // NPSN harus ada di ref_satuanpendidikan agar FK kodept valid
    public function upsert(User $user): bool
    {
        return $user->role === 'pt' && $user->pt()->exists();
    }

    public function delete(User $user, PelaksanaanKkn $pelaksanaanKkn): bool
    {
        return $user->role === 'pt' && $pelaksanaanKkn->kodept === $user->email;
    }
}
