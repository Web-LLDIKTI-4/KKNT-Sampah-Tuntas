<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Mahasiswa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mahasiswa';
    protected $guarded = [];
    protected $primaryKey = 'id_mahasiswa';
    //protected $with = ['user','sp','dplmentoring','logkegiatan','logbulanan','lokasi'];

    /**
     * Mahasiswa yang boleh dilihat user: admin semua, DPL bimbingannya,
     * PT mahasiswa dari PT & lokasi program yang sama, role lain tidak ada.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin' => $query,
            'dpl' => $query->whereHas('dplmentoring', fn ($q) => $q->where('email_dpl', $user->email)),
            'pt' => $query->where('kodept', $user->email)->where('location_program', $user->location_program),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function canBeViewedBy(User $user, ?string $email): bool
    {
        return $email !== null && static::visibleTo($user)->where('email', $email)->exists();
    }

    public function user()
    {
        return $this->hasOne(User::class,'email','email');
    }
    public function sp()
    {
        return $this->hasOne(Satuanpendidikan::class,'npsn','kodept');
    }
    public function dplmentoring()
    {
        return $this->hasOne(Dplmentoring::class, 'email_mahasiswa', 'email');
    }
    public function logkegiatan()
    {
        return $this->hasMany(Logkegiatan::class,'email','email');
    }
    public function logbulanan()
    {
        return $this->hasMany(Logbulanan::class,'email','email');
    }
    public function lokasi()
    {
        return $this->hasOne(Mahasiswa_lokasi::class,'id_mahasiswa','id_mahasiswa');
    }
    public function tugasakhir()
    {
        return $this->hasOne(Tugasakhir::class,'email','email');
    }

    public function locationProgram()
    {
        return $this->belongsTo(LokasiProgram::class,'location_program','id');
    }

    public function logkehadiran()
    {
        return $this->hasMany(Kehadiran::class,'email','email');
    }
}