<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Kpi extends Model
{
    protected $table = 'kpi';
    protected $guarded = ['id_kpi'];
    protected $primaryKey = 'id_kpi'; // Tentukan primary key sesuai dengan struktur tabel
    public function target()
    {
        return $this->hasMany(Kpitarget::class,'id_kpi','id_kpi');
    }
    public function capaian()
    {
        return $this->hasMany(Kpicapaian::class,'id_kpi','id_kpi');
    }
}