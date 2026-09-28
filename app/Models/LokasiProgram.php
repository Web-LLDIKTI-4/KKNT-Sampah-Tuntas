<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LokasiProgram extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lokasi_program';
    protected $fillable = [
        'gambar',
        'nama_lokasi',
    ];

    protected static function booted(): void
    {
        static::deleting(function ($lokasi) {
            if ($lokasi->gambar && Storage::disk('public')->exists($lokasi->gambar)) {
                Storage::disk('public')->delete($lokasi->gambar);
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'location_program', 'id');
    }

    public function mahasiswa() 
    {
        return $this->hasMany(User::class, 'location_program', 'id');
    }
}
