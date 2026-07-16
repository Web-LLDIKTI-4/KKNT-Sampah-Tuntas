<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Desa extends Model
{
    protected $table = 'desa';
    protected $guarded = ['id_desa'];
    protected $primaryKey = 'id_desa'; // Tentukan primary key sesuai dengan struktur tabel
    
    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class,'id_kecamatan','id_kecamatan');
    }
}