<?php
namespace App\Models;
use App\Models\Concerns\OwnedByEmail;
use App\Services\PenguranganSampahService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class CapaianKegiatan extends Model
{
    use HasFactory, HasUuids, OwnedByEmail;

    public const STATUS = ['Y' => 'Sudah', 'P' => 'Proses', 'N' => 'Belum'];

    public const BADGE = ['Y' => 'bg-success', 'P' => 'bg-warning', 'N' => 'bg-danger'];

    // Persen tuntas di halaman publik dihitung dari capaian; cache publik ikut direset
    protected static function booted(): void
    {
        static::saved(fn () => PenguranganSampah::forgetPublicCache());
        static::deleted(fn () => PenguranganSampah::forgetPublicCache());
    }

    // Badge berwarna tindak lanjut; status tidak dikenal dianggap Belum
    public static function statusBadge(?string $status): string
    {
        $status = array_key_exists((string) $status, self::STATUS) ? $status : 'N';

        return '<span class="badge '.self::BADGE[$status].'">'.self::STATUS[$status].'</span>';
    }

    protected $table = 'capaian_kegiatan';
    protected $guarded = ['id_capaian'];
    protected $primaryKey = 'id_capaian'; // Tentukan primary key sesuai dengan struktur tabel

    public function pjdesa()
    {
        return $this->hasOne(Pjdesa::class,'email','email');
    }
    public function kategoriKegiatan()
    {
        return $this->hasOne(KategoriKegiatan::class,'id_kategori','id_kategori');
    }
    public function dplMentoring()
    {
        return $this->belongsTo(
            Dplmentoring::class,
            'email', // foreign key di capaian_kegiatan
            'email_mahasiswa'      // owner key di dplmentoring
        );
    }

    // Tampilan mahasiswa: ketua miliknya; anggota capaian ketua (Pjdesa) di desa penempatannya
    public function scopeVisibleToMahasiswa(Builder $query, string $email): Builder
    {
        if (Pjdesa::where('email', $email)->exists()) {
            return $query->where($this->qualifyColumn('email'), $email);
        }
        $idDesa = app(PenguranganSampahService::class)->desaMahasiswa($email);

        return $query->whereHas('pjdesa', fn ($q) => $idDesa ? $q->where('id_desa', $idDesa) : $q->whereRaw('1 = 0'));
    }
}
