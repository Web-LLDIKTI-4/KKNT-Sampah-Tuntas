<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Dplmentoring extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dpl_mentoring';
    protected $primaryKey = 'id_mentoring'; 
    protected $guarded = [];
    
    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'email','email_mahasiswa');
    }
    public function tugasakhir()
    {
        return $this->hasOne(Tugasakhir::class,'email','email_mahasiswa');
    }

    public function scopeOfDpl(Builder $query, User $dpl): Builder
    {
        return $query->where('email_dpl', $dpl->email);
    }

    // Apakah mahasiswa ini bimbingan DPL tersebut
    public static function isMentor(User $dpl, ?string $emailMahasiswa): bool
    {
        return $emailMahasiswa !== null
            && static::ofDpl($dpl)->where('email_mahasiswa', $emailMahasiswa)->exists();
    }

    public function capaianKegiatan()
    {
        return $this->hasMany(
            CapaianKegiatan::class,
            'email', 
            'email_mahasiswa'
        );
    }
}