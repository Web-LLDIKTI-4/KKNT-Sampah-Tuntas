<?php
namespace App\Models;
use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Kpicapaian extends Model
{
    use HasFactory, HasUuids, OwnedByEmail;

    public const STATUS = ['Y' => 'Sudah Selesai', 'P' => 'Proses', 'N' => 'Belum Ditindaklanjuti'];

    protected $table = 'kpi_capaian';
    protected $guarded = ['id_capaian'];
    protected $primaryKey = 'id_capaian'; // Tentukan primary key sesuai dengan struktur tabel

    // Capaian % dihitung dari tindak lanjut: Sudah Selesai = 100, selain itu 0
    public static function persen(?string $status): float
    {
        return $status === 'Y' ? 100.0 : 0.0;
    }

    public static function formatAngka($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format(round((float) $value), 0, ',', '.');
    }

    public function capaianPersen(): float
    {
        return static::persen($this->status_capaian);
    }

    public function pjdesa()
    {
        return $this->hasOne(Pjdesa::class,'email','email');
    }
    public function kpi()
    {
        return $this->hasOne(Kpi::class,'id_kpi','id_kpi');
    }
    public function target()
    {
        return $this->hasOne(Kpitarget::class,'id_target','id_target');
    }
    public function dplMentoring()
    {
        return $this->belongsTo(
            Dplmentoring::class,
            'email', // foreign key di kpicapaian
            'email_mahasiswa'      // owner key di dplmentoring
        );
    }
}