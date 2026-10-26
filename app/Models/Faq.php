<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Faq extends Model
{
    use HasFactory;

    // Cache daftar FAQ di landing page; dihapus setiap ada perubahan data
    public const PUBLIC_CACHE_KEY = 'landing.faq';

    protected $fillable = [
        'pertanyaan',
        'jawaban',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
    }
}
