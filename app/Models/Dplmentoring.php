<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Dplmentoring extends Model
{
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

    public function kpiCapaian()
    {
        return $this->hasMany(
            Kpicapaian::class,
            'email', 
            'email_mahasiswa'
        );
    }
}