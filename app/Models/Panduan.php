<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Panduan extends Model
{
    use HasFactory, HasUuids;

    // Cache daftar panduan aktif di halaman login; dihapus setiap ada perubahan data
    public const PUBLIC_CACHE_KEY = 'login.panduan';

    // File panduan disimpan privat; akses hanya lewat route download
    public const DISK = 'local';

    protected $table = 'panduan';
    protected $primaryKey = 'id_panduan';
    protected $keyType = 'string';
    public $incrementing = false;

    // Kolom DB peruntukan sengaja tidak fillable: tidak dipakai sejak iterasi 2 (default 'semua'),
    // disimpan untuk penargetan di masa depan
    protected $fillable = [
        'judul',
        'deskripsi',
        'file_path',
        'nama_file',
        'ukuran',
        'mime',
        'is_aktif',
        'uploaded_by',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'ukuran' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Panduan $panduan) {
            if ($panduan->file_path && Storage::disk(self::DISK)->exists($panduan->file_path)) {
                Storage::disk(self::DISK)->delete($panduan->file_path);
            }
        });

        static::saved(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }
}
