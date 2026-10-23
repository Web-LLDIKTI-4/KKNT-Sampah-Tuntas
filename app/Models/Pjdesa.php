<?php
namespace App\Models;
use App\Observers\PetaSebaranCacheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
#[ObservedBy(PetaSebaranCacheObserver::class)]
class Pjdesa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pj_desa';
    protected $guarded = ['id_pjdesa'];
    protected $primaryKey = 'id_pjdesa'; // Tentukan primary key sesuai dengan struktur tabel

    public const MOBILISASI_STATUSES = ['belum', 'sudah'];

    public function desa()
    {
        return $this->belongsTo(Desa::class,'id_desa','id_desa');
    }
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class,'email','email');
    }

    // PT: hanya kelompok yang ketuanya mahasiswa PT tsb (konsisten dengan Mahasiswa::scopeVisibleTo)
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin', 'kepala', 'pemda' => $query,
            'pt' => $query->whereHas('mahasiswa', fn ($m) => $m->visibleTo($user)),
            default => $query->whereRaw('1 = 0'),
        };
    }
}