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

    public const STATUS = ['Y' => 'Sudah', 'P' => 'Proses', 'N' => 'Belum'];

    public const BADGE = ['Y' => 'bg-success', 'P' => 'bg-warning', 'N' => 'bg-danger'];

    // Persen tuntas di halaman publik dihitung dari capaian; cache publik ikut direset
    protected static function booted(): void
    {
        static::saved(fn () => Kpisampah::forgetPublicCache());
        static::deleted(fn () => Kpisampah::forgetPublicCache());
    }

    // Badge berwarna tindak lanjut; status tidak dikenal dianggap Belum
    public static function statusBadge(?string $status): string
    {
        $status = array_key_exists((string) $status, self::STATUS) ? $status : 'N';

        return '<span class="badge '.self::BADGE[$status].'">'.self::STATUS[$status].'</span>';
    }

    protected $table = 'kpi_capaian';
    protected $guarded = ['id_capaian'];
    protected $primaryKey = 'id_capaian'; // Tentukan primary key sesuai dengan struktur tabel

    public function pjdesa()
    {
        return $this->hasOne(Pjdesa::class,'email','email');
    }
    public function kpi()
    {
        return $this->hasOne(Kpi::class,'id_kpi','id_kpi');
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