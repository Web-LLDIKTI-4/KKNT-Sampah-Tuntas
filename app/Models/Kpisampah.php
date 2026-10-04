<?php

namespace App\Models;

use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
        'pengurangan_organik' => 'float',
        'pengurangan_anorganik' => 'float',
        'pengurangan' => 'float',
        'residu' => 'float',
        'persen_pengurangan' => 'float',
    ];

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

    public static function klaster($persen): ?string
    {
        return match (true) {
            $persen === null => null,
            (float) $persen >= self::TARGET_PENGURANGAN => 'hijau',
            (float) $persen >= self::BATAS_KUNING => 'kuning',
            default => 'merah',
        };
    }

    // Kelas warna sel tabel sesuai klaster; '' bila belum ada data
    public static function warnaSel($persen): string
    {
        return self::KLASTER[static::klaster($persen)]['sel'] ?? '';
    }

    // Capaian KPI: min(100, persen / 20 × 100)
    public static function capaian($persen): ?float
    {
        if ($persen === null) {
            return null;
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
