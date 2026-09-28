<?php
namespace App\Models;
use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Kehadiran extends Model
{
    use HasFactory, HasUuids, OwnedByEmail;

    protected $table = 'kehadiran';
    protected $primaryKey = 'id_kehadiran'; // Tentukan primary key sesuai dengan struktur tabel

    protected $guarded = [];
    protected $with = ['mahasiswa','dplmentoring'];

    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'email','email');
    }
    
    public function dplmentoring()
    {
        return $this->hasOne(Dplmentoring::class,'email_mahasiswa','email');
    }
}