<?php

namespace App\Support;

// Wraps a keyword for "contains" LIKE with %, _ and \ matched literally
class LikeKeyword
{
    public static function contains(string $keyword): string
    {
        return '%'.addcslashes($keyword, '%_\\').'%';
    }
}
