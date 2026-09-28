<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Nilaikonversi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'nilai_konversi';
    protected $guarded = [];
    protected $primaryKey = 'id_konversi';
    
    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'id_mahasiswa','id_mahasiswa');
    }
}