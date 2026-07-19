<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LokasiProgram extends Model
{
    use HasFactory;

    protected $table = 'lokasi_program';
    protected $fillable = [
        'id',
        'gambar',
        'nama_lokasi',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

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
