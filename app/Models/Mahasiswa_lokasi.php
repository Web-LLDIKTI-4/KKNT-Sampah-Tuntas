<?php
namespace App\Models;
use App\Observers\PetaSebaranCacheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
#[ObservedBy(PetaSebaranCacheObserver::class)]
class Mahasiswa_lokasi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mahasiswa_lokasi';
    protected $guarded = [];
    protected $primaryKey = 'id_lokasi';
    
    public function desa()
    {
        return $this->hasOne(Desa::class,'id_desa','id_desa');
    }
}