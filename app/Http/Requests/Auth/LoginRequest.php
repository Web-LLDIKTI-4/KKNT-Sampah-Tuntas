<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username harus diisi',
            'password.required' => 'Password harus diisi',
        ];
    }

    /**
     * Coba login dengan pembatasan percobaan (anti brute force).
     */
    public function authenticate(): bool
    {
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            $this->fail("Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.");
        }

        if (! Auth::attempt(['email' => $this->string('username')->toString(), 'password' => $this->input('password')])) {
            RateLimiter::hit($key, 60);

            return false;
        }

        RateLimiter::clear($key);

        return true;
    }

    public function fail(string $message): never
    {
        throw new HttpResponseException(response()->json(['success' => false, 'messages' => $message]));
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->fail(implode(', ', $validator->errors()->all()));
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
