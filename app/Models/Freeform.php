<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Freeform extends Model
{
    protected $table = 'nilai_freeform';
    protected $guarded = [];
    protected $primaryKey = 'id_freeform';
    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'id_mahasiswa','id_mahasiswa');
    }
}