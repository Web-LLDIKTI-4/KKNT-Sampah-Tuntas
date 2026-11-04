<?php

namespace App\Services;

use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

// Kelayakan & penugasan mahasiswa ke DPL (dipakai menu DPL dan admin)
class MentoringAssignmentService
{
    // Mahasiswa se-PT yang belum punya DPL
    public function candidates(string $kodept): Builder
    {
        return Mahasiswa::where('kodept', $kodept)->whereDoesntHave('dplmentoring');
    }

    // Returns rows actually stored; duplicates skipped via unique email_mahasiswa
    public function assign(string $emailDpl, string $kodept, array $emails): int
    {
        $eligible = $this->candidates($kodept)->whereIn('email', $emails)->pluck('email');

        $now = now();
        $rows = $eligible->map(fn ($email) => [
            'id_mentoring' => (string) Str::uuid(),
            'email_mahasiswa' => $email,
            'email_dpl' => $emailDpl,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        return $rows ? Dplmentoring::insertOrIgnore($rows) : 0;
    }
}
