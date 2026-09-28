<?php
namespace App\Models;
use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Logkegiatan extends Model
{
    use HasFactory, HasUuids, OwnedByEmail;

    protected $table = 'logkegiatan';
    protected $primaryKey = 'id_log'; // Tentukan primary key sesuai dengan struktur tabel

    protected $guarded = [];
    protected $with = ['kpi','mahasiswa','dplmentoring'];

    public function kpi()
    {
        return $this->hasOne(Kpi::class,'id_kpi','id_kpi');
    }
    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'email','email');
    }
    public function dplmentoring()
    {
        return $this->hasOne(Dplmentoring::class,'email_mahasiswa','email');
    }
}