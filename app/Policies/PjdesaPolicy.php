<?php

namespace App\Policies;

use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\User;

class PjdesaPolicy
{
    // Hanya PT pemilik ketua kelompok; admin read-only
    public function updateMobilisasi(User $user, Pjdesa $pjdesa): bool
    {
        return $user->role === 'pt' && Mahasiswa::canBeViewedBy($user, $pjdesa->email);
    }

    // PT menunjuk mahasiswanya sendiri sebagai ketua
    public function assign(User $user, Mahasiswa $mahasiswa): bool
    {
        return $user->role === 'pt' && Mahasiswa::canBeViewedBy($user, $mahasiswa->email);
    }

    public function revoke(User $user, Pjdesa $pjdesa): bool
    {
        return $this->updateMobilisasi($user, $pjdesa);
    }
}
