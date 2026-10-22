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

    // Role yang dikunci sampai PT-nya mengisi pelaksanaan KKN
    public const PELAKSANAAN_LOCK_ROLES = ['mahasiswa', 'dpl'];

    // Memo per instance (per request) untuk isLockedByPelaksanaan()
    protected ?bool $lockedByPelaksanaan = null;

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
            // Ascending id: belongsTo keeps the last row per email, i.e. MAX(id) like the list subqueries & export
            ->with([
                'mahasiswa' => fn ($q) => $q->select('email', 'nim', 'kodept')->orderBy('id_mahasiswa'),
                'mahasiswa.sp:npsn,nm_lemb',
                'dpl' => fn ($q) => $q->select('email', 'nidn', 'kodept')->orderBy('id_dpl'),
                'dpl.sp:npsn,nm_lemb',
                'pt:npsn,nm_lemb',
            ])
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($kodept, fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('role', 'mahasiswa')->whereIn('email', Mahasiswa::select('email')->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('role', 'dpl')->whereIn('email', Dpl::select('email')->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('role', 'pt')->where('email', $kodept))));
    }

    // Role pemantau read-only (kepala & pemda)
    public function isPemantau(): bool
    {
        return in_array($this->role, ['kepala', 'pemda'], true);
    }

    // Mahasiswa/DPL terkunci bila PT-nya belum mengisi pelaksanaan KKN
    public function isLockedByPelaksanaan(): bool
    {
        if (! config('app.pelaksanaan_lock') || ! in_array($this->role, self::PELAKSANAAN_LOCK_ROLES, true)) {
            return false;
        }

        if ($this->lockedByPelaksanaan !== null) {
            return $this->lockedByPelaksanaan;
        }

        $kodept = $this->role === 'mahasiswa' ? $this->mahasiswa?->kodept : $this->dpl?->kodept;

        return $this->lockedByPelaksanaan = blank($kodept)
            || ! PelaksanaanKkn::where('kodept', $kodept)->exists();
    }
}
