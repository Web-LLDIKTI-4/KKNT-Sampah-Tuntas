<?php

namespace App\Models;

use App\Models\Concerns\OwnedByEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendataanPemilahanSampah extends Model
{
    use HasFactory, HasUuids, OwnedByEmail;

    protected $table = 'pendataan_pemilahan_sampah';

    protected $primaryKey = 'id_pendataan';

    protected $guarded = ['id_pendataan'];

    protected $casts = [
        'memilah' => 'boolean',
        'organik_kg' => 'float',
        'anorganik_kg' => 'float',
        'residu_kg' => 'float',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => PenguranganSampah::forgetPublicCache());
        static::deleted(fn () => PenguranganSampah::forgetPublicCache());
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'email', 'email');
    }
}
