<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Mahasiswa_lokasi extends Model
{
    protected $table = 'mahasiswa_lokasi';
    protected $guarded = [];
    protected $primaryKey = 'id_lokasi';
    
    public function desa()
    {
        return $this->hasOne(Desa::class,'id_desa','id_desa');
    }
}