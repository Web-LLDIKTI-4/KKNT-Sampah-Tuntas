<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'location_program',
        'password',
    ];
    protected $with = ['mahasiswa','dpl'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function locationProgram()
    {
        return $this->belongsTo(LokasiProgram::class,'location_program','id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class,'email','email');
    }
    
    public function dpl()
    {
        return $this->belongsTo(Dpl::class,'email','email');
    }

    public function pt()
    {
        return $this->belongsTo(Satuanpendidikan::class,'email','npsn');
    }

    // Users shown in Kelola Pengguna, filtered by role and PT (kodept)
    public function scopeManaged(Builder $query, array $filters = []): Builder
    {
        $kodept = $filters['kodept'] ?? null;

        return $query->whereIn('role', ['mahasiswa', 'dpl', 'pt', 'kepala', 'pemda'])
            ->with(['mahasiswa.sp', 'dpl.sp', 'pt'])
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($kodept, fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('role', 'mahasiswa')->whereHas('mahasiswa', fn ($q) => $q->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('role', 'dpl')->whereHas('dpl', fn ($q) => $q->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('role', 'pt')->where('email', $kodept))));
    }

    // Role pemantau read-only (kepala & pemda)
    public function isPemantau(): bool
    {
        return in_array($this->role, ['kepala', 'pemda'], true);
    }
}
