<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

// Email must not belong to a mahasiswa/DPL record (LOWER(TRIM) compare); the user's own current email is allowed
class EmailNotInPersonData implements ValidationRule
{
    public function __construct(private ?string $ownEmail = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $email = mb_strtolower(trim($value));
        if ($this->ownEmail !== null && mb_strtolower(trim($this->ownEmail)) === $email) {
            return;
        }

        foreach (['mahasiswa', 'dpl'] as $table) {
            if (DB::table($table)->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
                $fail('Email sudah digunakan oleh data mahasiswa/DPL lain!');

                return;
            }
        }
    }
}
