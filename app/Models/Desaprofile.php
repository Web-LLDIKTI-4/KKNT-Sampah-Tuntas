<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Desaprofile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'desa_profile';
    protected $guarded = ['id_profile'];
    protected $primaryKey = 'id_profile'; // Tentukan primary key sesuai dengan struktur tabel
    public function desa()
    {
        return $this->belongsTo(Desa::class,'id_desa','id_desa');
    }
}