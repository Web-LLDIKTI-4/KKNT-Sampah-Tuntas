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

    // Capaian % hanya untuk isian dengan tindak lanjut Sudah Selesai; null = tidak dihitung
    public static function persen(?string $status, $realisasi, $target): ?float
    {
        return $status === 'Y' ? static::capaianRata($realisasi, $target) : null;
    }

    // min(realisasi / target × 100, 100); null bila realisasi kosong atau target tidak valid
    public static function capaianRata($realisasi, $target): ?float
    {
        if ($realisasi === null || (float) $target <= 0) {
            return null;
        }

        return min((float) $realisasi / (float) $target * 100, 100.0);
    }

    public static function formatAngka($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format(round((float) $value), 0, ',', '.');
    }

    public static function formatPersen(?float $value): string
    {
        return $value === null ? '-' : static::formatAngka($value).'%';
    }

    public function capaianPersen(): ?float
    {
        return static::persen($this->status_capaian, $this->realisasi, $this->target?->target);
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