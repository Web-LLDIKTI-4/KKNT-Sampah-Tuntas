<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class SettingRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'plama' => ['required', 'string', 'current_password'],
            'pbaru' => ['required', 'string', 'max:255', 'different:plama', Password::min(8)->letters()->numbers()],
            'pbaruulangi' => ['required', 'same:pbaru'],
        ];
    }

    public function messages(): array
    {
        return [
            'plama.required' => 'Kata sandi lama harus diisi.',
            'plama.current_password' => 'Kata sandi lama salah!',
            'pbaru.required' => 'Kata sandi baru harus diisi.',
            'pbaru.different' => 'Kata sandi baru harus berbeda dengan kata sandi lama.',
            'pbaru.min' => 'Kata sandi baru minimal 8 karakter.',
            'pbaru.letters' => 'Kata sandi baru harus mengandung huruf.',
            'pbaru.numbers' => 'Kata sandi baru harus mengandung angka.',
            'pbaruulangi.required' => 'Konfirmasi kata sandi baru harus diisi.',
            'pbaruulangi.same' => 'Kata sandi baru harus sama dengan konfirmasinya!',
        ];
    }
}
