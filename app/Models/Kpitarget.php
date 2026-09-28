<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Kpitarget extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kpi_target';
    protected $guarded = [];
    protected $primaryKey = 'id_target'; // Tentukan primary key sesuai dengan struktur tabel
    public function kpi()
    {
        return $this->hasOne(Kpi::class,'id_kpi','id_kpi');
    }
    
}