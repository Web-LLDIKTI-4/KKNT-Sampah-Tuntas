<?php
namespace App\Models;
use App\Observers\PetaSebaranCacheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
#[ObservedBy(PetaSebaranCacheObserver::class)]
class Desa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'desa';
    protected $guarded = ['id_desa'];
    protected $primaryKey = 'id_desa'; // Tentukan primary key sesuai dengan struktur tabel
    
    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class,'id_kecamatan','id_kecamatan');
    }
}