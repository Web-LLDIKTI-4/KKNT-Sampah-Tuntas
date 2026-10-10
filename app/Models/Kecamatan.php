<?php
namespace App\Models;
use App\Observers\PetaSebaranCacheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
#[ObservedBy(PetaSebaranCacheObserver::class)]
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