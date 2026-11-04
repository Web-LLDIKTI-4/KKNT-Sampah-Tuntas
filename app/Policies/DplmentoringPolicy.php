<?php

namespace App\Policies;

use App\Models\User;

class DplmentoringPolicy
{
    // Kelola penugasan seluruh DPL (menu admin); jalur DPL tidak memakai policy ini
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
