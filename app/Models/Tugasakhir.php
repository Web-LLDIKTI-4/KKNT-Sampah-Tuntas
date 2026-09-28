<?php
namespace App\Models;
use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Tugasakhir extends Model
{
    use HasFactory, HasUuids, OwnedByEmail;

    protected $table = 'tugasakhir';
    protected $guarded = ['id_tugasakhir'];
    protected $primaryKey = 'id_tugasakhir'; // Tentukan primary key sesuai dengan struktur tabel
    protected $with = ['mahasiswa','dplmentoring'];

    public function user()
    {
        return $this->belongsTo(User::class,'email','email');
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