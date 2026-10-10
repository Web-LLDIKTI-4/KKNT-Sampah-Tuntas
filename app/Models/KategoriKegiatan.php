<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class KategoriKegiatan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kategori_kegiatan';
    protected $guarded = ['id_kategori'];
    protected $primaryKey = 'id_kategori'; // Tentukan primary key sesuai dengan struktur tabel
    public function capaian()
    {
        return $this->hasMany(CapaianKegiatan::class,'id_kategori','id_kategori');
    }
}