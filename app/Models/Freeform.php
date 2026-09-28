<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Freeform extends Model
{
    use HasFactory, HasUuids;

    public const KOMPONEN = [
        'Pengembangan kepribadian mahasiswa (Personality Development)',
        'Pemberdayaan Masyarakat (Community Empowerment)',
        'pengembangan institusi (Institutional development)',
        'Kemampuan Berkomunikasi',
        'Kemampuan Bekerja sama',
        'Kreativitas',
    ];

    protected $table = 'nilai_freeform';
    protected $guarded = [];
    protected $primaryKey = 'id_freeform';
    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,'id_mahasiswa','id_mahasiswa');
    }
}