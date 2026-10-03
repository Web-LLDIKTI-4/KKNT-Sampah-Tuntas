<?php

namespace App\Support;

use Illuminate\Support\Number;

class FileSize
{
    // Format Indonesia (koma desimal), mis. "1,2 MB"
    public static function format(int $bytes): string
    {
        return Number::withLocale('id', fn () => Number::fileSize($bytes, precision: 1));
    }
}
