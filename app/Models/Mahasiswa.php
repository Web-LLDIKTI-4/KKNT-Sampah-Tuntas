<?php
namespace App\Models;
use App\Observers\PetaSebaranCacheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; 
#[ObservedBy(PetaSebaranCacheObserver::class)]
class Mahasiswa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mahasiswa';
    protected $guarded = [];
    protected $primaryKey = 'id_mahasiswa';
    //protected $with = ['user','sp','dplmentoring','logkegiatan','logbulanan','lokasi'];

    // Alias => source column of withLatestLokasi()
    public const LOKASI_COLUMNS = ['nama_kecamatan' => 'k.kecamatan', 'nama_desa' => 'd.desa'];

    // Adds nama_kecamatan & nama_desa as correlated subqueries (no N+1, no duplicate rows)
    public function scopeWithLatestLokasi(Builder $query): Builder
    {
        return $query->addSelect(array_map(self::latestLokasi(...), self::LOKASI_COLUMNS));
    }

    // Value from the latest-year mahasiswa_lokasi row with a desa (null when none)
    public static function latestLokasi(string $column): QueryBuilder
    {
        return self::lokasiWithDesa()
            ->leftJoin('desa as d', 'd.id_desa', '=', 'ml.id_desa')
            ->leftJoin('kecamatan as k', 'k.id_kecamatan', '=', 'd.id_kecamatan')
            ->orderByDesc('ml.tahun')
            ->orderByDesc('ml.updated_at')
            ->orderByDesc('ml.id_lokasi')
            ->limit(1)
            ->select($column);
    }

    public const LOKASI_STATUSES = ['belum', 'sudah'];

    // lokasi_status filter: 'sudah' = has a lokasi row with desa, 'belum' = none; other values ignored
    public function scopeLokasiStatus(Builder $query, mixed $status): Builder
    {
        return match ($status) {
            'sudah' => $query->whereExists(self::lokasiWithDesa()),
            'belum' => $query->whereNotExists(self::lokasiWithDesa()),
            default => $query,
        };
    }

    // "Lokasi set" rows: shared definition for withLatestLokasi() & lokasiStatus()
    private static function lokasiWithDesa(): QueryBuilder
    {
        return DB::table('mahasiswa_lokasi as ml')
            ->whereColumn('ml.id_mahasiswa', 'mahasiswa.id_mahasiswa')
            ->whereNotNull('ml.id_desa');
    }

    /**
     * Mahasiswa yang boleh dilihat user: admin semua, DPL bimbingannya,
     * PT mahasiswa dari PT & lokasi program yang sama, role lain tidak ada.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin', 'kepala', 'pemda' => $query,
            'dpl' => $query->whereHas('dplmentoring', fn ($q) => $q->where('email_dpl', $user->email)),
            'pt' => $query->where('kodept', $user->email),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function canBeViewedBy(User $user, ?string $email): bool
    {
        return $email !== null && static::visibleTo($user)->where('email', $email)->exists();
    }

    public function hasAccount(): bool
    {
        return filled($this->email) && User::where('email', $this->email)->exists();
    }

    // Email adalah kunci relasi: terkunci bila sudah dipakai akun, pj_desa, atau dpl_mentoring
    public function isEmailLocked(): bool
    {
        return $this->hasAccount()
            || (filled($this->email) && ($this->pjdesa()->exists() || $this->dplmentoring()->exists()));
    }

    public function user()
    {
        return $this->hasOne(User::class,'email','email');
    }
    public function sp()
    {
        return $this->hasOne(Satuanpendidikan::class,'npsn','kodept');
    }
    public function pjdesa()
    {
        return $this->hasOne(Pjdesa::class,'email','email');
    }
    public function dplmentoring()
    {
        return $this->hasOne(Dplmentoring::class, 'email_mahasiswa', 'email');
    }
    public function logkegiatan()
    {
        return $this->hasMany(Logkegiatan::class,'email','email');
    }

    public function pendataanPemilahanSampah()
    {
        return $this->hasMany(PendataanPemilahanSampah::class, 'email', 'email');
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