<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Evaluasikegiatan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'evaluasi_kegiatan';
    protected $guarded = [];
    protected $primaryKey = 'id_evaluasi';
    public function jawaban()
    {
        return $this->hasMany(Evaluasikegiatanjawaban::class,'id_evaluasi','id_evaluasi');
    }
    public function jawabanPeruserTahun($userId, $year)
    {
        return $this->hasOne(Evaluasikegiatanjawaban::class, 'id_evaluasi', 'id_evaluasi')
                    ->where('user', $userId)
                    ->where('tahun', $year);
    }
}