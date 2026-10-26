<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Faq extends Model
{
    use HasFactory;

    // Cache daftar FAQ di landing page; dihapus setiap ada perubahan data
    public const PUBLIC_CACHE_KEY = 'landing.faq.v2';

    // Gambar jawaban diakses publik di landing page
    public const DISK = 'public';

    public const IMAGE_DIR = 'faq';

    public const TYPE_TEXT = 'text';

    public const TYPE_IMAGE = 'image';

    public const TYPE_BOTH = 'both';

    public const TYPES = [self::TYPE_TEXT, self::TYPE_IMAGE, self::TYPE_BOTH];

    protected $fillable = [
        'pertanyaan',
        'jawaban',
        'answer_type',
        'answer_image',
    ];

    protected $attributes = [
        'answer_type' => self::TYPE_TEXT,
    ];

    public function hasText(): bool
    {
        return in_array($this->answer_type, [self::TYPE_TEXT, self::TYPE_BOTH], true) && filled($this->jawaban);
    }

    public function hasImage(): bool
    {
        return $this->answer_type !== self::TYPE_TEXT && filled($this->answer_image);
    }

    // URL relatif root (tanpa host APP_URL) agar tidak bergantung host/port yang diakses
    protected function answerImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasImage()
            ? parse_url(Storage::disk(self::DISK)->url($this->answer_image), PHP_URL_PATH)
            : null);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
    }
}
