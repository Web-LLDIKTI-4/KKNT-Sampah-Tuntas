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

    // Capaian % = realisasi / target, maksimal 100
    public static function persen(?float $realisasi, ?float $target): float
    {
        if (! $target || $target <= 0 || $realisasi === null) {
            return 0.0;
        }

        return round(min(100, $realisasi / $target * 100), 2);
    }

    public static function formatAngka($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }

    public function capaianPersen(): float
    {
        return static::persen($this->realisasi, $this->target?->target);
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