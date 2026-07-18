<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Evaluasikegiatanjawaban extends Model
{
    protected $table = 'evaluasi_kegiatan_jawaban';
    protected $guarded = [];
    protected $primaryKey = 'id';
    
    public function evaluasikegiatan()
    {
        return $this->hasOne(Evaluasikegiatan::class,'id_evaluasi','id_evaluasi');
    }

    public function sp()
    {
        return $this->hasOne(Satuanpendidikan::class,'kodept','npsn');
    }
}