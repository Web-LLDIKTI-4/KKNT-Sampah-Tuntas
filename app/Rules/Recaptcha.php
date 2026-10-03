<?php

namespace App\Rules;

use App\Services\RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

class Recaptcha implements ValidationRule, ValidatorAwareRule
{
    public const MESSAGE = 'Verifikasi keamanan gagal. Silakan muat ulang halaman dan coba lagi.';

    private ?Validator $validator = null;

    /** @param  array<int, string>  $dependsOn  field yang harus valid dulu sebelum memanggil Google */
    public function __construct(private string $action, private array $dependsOn = []) {}

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Field lain sudah gagal → tidak perlu memanggil Google
        if ($this->validator?->errors()->hasAny($this->dependsOn)) {
            return;
        }

        $token = is_string($value) ? $value : null;

        if (! app(RecaptchaService::class)->verify($token, $this->action, request()->ip())) {
            $fail(self::MESSAGE);
        }
    }
}
