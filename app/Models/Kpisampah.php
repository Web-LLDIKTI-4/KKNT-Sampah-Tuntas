<?php

namespace App\Models;

use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Kpisampah extends Model
{
    use HasUuids, OwnedByEmail;

    protected $table = 'kpi_sampah';
    protected $primaryKey = 'id_sampah';
    protected $guarded = ['id_sampah'];

    protected $casts = [
        'bulan' => 'date',
        'persen_ketaatan' => 'float',
        'timbulan' => 'float',
        'organik_sumber' => 'float',
        'organik_dlh' => 'float',
        'anorganik_sumber' => 'float',
        'pengurangan' => 'float',
        'belum_terkelola' => 'float',
        'persen_pengurangan' => 'float',
    ];

    // Cache halaman publik (login); di-reset hook model & seeder agar UUID/bulan di HTML tidak basi
    public const PUBLIC_BULAN_CACHE_KEY = 'login.bulanList';

    public const PUBLIC_VERSION_CACHE_KEY = 'login.capaian.ver';

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetPublicCache());
        static::deleted(fn () => self::forgetPublicCache());
    }

    // Key capaian berisi md5 filter, jadi diganti versinya (entry lama kedaluwarsa sendiri)
    public static function forgetPublicCache(): void
    {
        Cache::forget(self::PUBLIC_BULAN_CACHE_KEY);
        Cache::forever(self::PUBLIC_VERSION_CACHE_KEY, (string) Str::ulid());
    }

    // Target KPI terpenuhi bila persentase pengurangan sampah per bulan minimal 20%
    public const TARGET_PENGURANGAN = 20.0;

    public static function terpenuhi($persen): ?bool
    {
        return $persen === null ? null : (float) $persen >= self::TARGET_PENGURANGAN;
    }

    // Klaster PT/wilayah berdasarkan persentase pengurangan sampah
    public const BATAS_KUNING = 10.0;

    public const KLASTER = [
        'hijau' => ['label' => 'Hijau', 'ket' => '≥ 20%', 'sel' => 'table-success', 'badge' => 'bg-success', 'rgb' => 'C6EFCE'],
        'kuning' => ['label' => 'Kuning', 'ket' => '10% – < 20%', 'sel' => 'table-warning', 'badge' => 'bg-warning', 'rgb' => 'FFEB9C'],
        'merah' => ['label' => 'Merah', 'ket' => '< 10%', 'sel' => 'table-danger', 'badge' => 'bg-danger', 'rgb' => 'FFC7CE'],
    ];

    // $strict: hijau bila > 20% (halaman publik); default >= 20%
    public static function klaster($persen, bool $strict = false): ?string
    {
        return match (true) {
            $persen === null => null,
            $strict ? (float) $persen > self::TARGET_PENGURANGAN : (float) $persen >= self::TARGET_PENGURANGAN => 'hijau',
            (float) $persen >= self::BATAS_KUNING => 'kuning',
            default => 'merah',
        };
    }

    // Kelas warna sel tabel sesuai klaster; '' bila belum ada data
    public static function warnaSel($persen, bool $strict = false): string
    {
        return self::KLASTER[static::klaster($persen, $strict)]['sel'] ?? '';
    }

    // Capaian KPI: min(100, persen / 20 × 100); $strict: 100% hanya bila > 20%, selain itu maks 99,99
    public static function capaian($persen, bool $strict = false): ?float
    {
        if ($persen === null) {
            return null;
        }

        if ($strict) {
            return (float) $persen > self::TARGET_PENGURANGAN
                ? 100.0
                : min(99.99, round((float) $persen / self::TARGET_PENGURANGAN * 100, 2));
        }

        return min(100.0, round((float) $persen / self::TARGET_PENGURANGAN * 100, 2));
    }

    // bagian / total × 100; null bila total 0
    public static function persen($bagian, $total): ?float
    {
        return (float) $total > 0 ? round((float) $bagian / (float) $total * 100, 2) : null;
    }

    public static function formatAngka($value, int $desimal = 0): string
    {
        return $value === null ? '-' : number_format((float) $value, $desimal, ',', '.');
    }

    public static function formatPersen(?float $value): string
    {
        return $value === null ? '-' : number_format($value, 2, ',', '.').'%';
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'id_desa', 'id_desa');
    }

    public function pjdesa()
    {
        return $this->belongsTo(Pjdesa::class, 'id_pjdesa', 'id_pjdesa');
    }
}
