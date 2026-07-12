<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Pjdesa extends Model
{
    protected $table = 'pj_desa';
    protected $guarded = ['id_pjdesa'];
    protected $primaryKey = 'id_pjdesa'; // Tentukan primary key sesuai dengan struktur tabel
    public function desa()
    {
        return $this->belongsTo(Desa::class,'id_desa','id_desa');
    }
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class,'email','email');
    }
}