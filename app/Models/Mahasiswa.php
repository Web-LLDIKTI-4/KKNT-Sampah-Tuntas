<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';
    protected $guarded = [];
    protected $primaryKey = 'id_mahasiswa';
    //protected $with = ['user','sp','dplmentoring','logkegiatan','logbulanan','lokasi'];

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