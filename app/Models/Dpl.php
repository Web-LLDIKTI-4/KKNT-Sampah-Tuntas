<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Dpl extends Model
{
    protected $table = 'dpl';
    protected $guarded = [];
    protected $primaryKey = 'id_dpl';
    
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