<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Kpicapaian extends Model
{
    protected $table = 'kpi_capaian';
    protected $guarded = ['id_capaian'];
    protected $primaryKey = 'id_capaian'; // Tentukan primary key sesuai dengan struktur tabel

    public function pjdesa()
    {
        return $this->hasOne(Pjdesa::class,'email','email');
    }
    public function kpi()
    {
        return $this->hasOne(Kpi::class,'id_kpi','id_kpi');
    }
    public function target()
    {
        return $this->hasOne(Kpitarget::class,'id_target','id_target');
    }
    public function dplMentoring()
    {
        return $this->belongsTo(
            Dplmentoring::class,
            'email', // foreign key di kpicapaian
            'email_mahasiswa'      // owner key di dplmentoring
        );
    }
}