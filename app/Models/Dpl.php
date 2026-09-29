<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Dpl extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dpl';
    protected $guarded = [];
    protected $primaryKey = 'id_dpl';

    // DPL yang boleh dilihat: admin/kepala semua, PT sesuai kodept akunnya
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin', 'kepala' => $query,
            'pt' => $query->where('kodept', $user->email),
            default => $query->whereRaw('1 = 0'),
        };
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
        return $this->hasOne(Dplmentoring::class,'email_dpl','email');
    }
    public function dpllaporan()
    {
        return $this->hasMany(Dpllaporan::class,'email','email');
    }
    public function locationProgram()
    {
        return $this->hasOne(LokasiProgram::class,'id','location_program');
    }
}