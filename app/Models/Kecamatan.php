<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Kecamatan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kecamatan';
    protected $guarded = ['id_kecamatan'];
    protected $primaryKey = 'id_kecamatan'; // Tentukan primary key sesuai dengan struktur tabel
    
    public function desa()
    {
        return $this->hasMany(Desa::class,'id_kecamatan','id_kecamatan');
    }
}