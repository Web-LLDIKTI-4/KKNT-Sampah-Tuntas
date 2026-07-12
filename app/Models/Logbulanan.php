<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Logbulanan extends Model
{
    protected $table = 'logkegiatan_bulanan';
    protected $primaryKey = 'id_logbulanan'; 
    protected $guarded = [];
    
    protected $with = ['mahasiswa','dplmentoring'];

    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'email','email');
    }
    public function dplmentoring()
    {
        return $this->hasOne(Dplmentoring::class,'email_mahasiswa','email');
    }
}