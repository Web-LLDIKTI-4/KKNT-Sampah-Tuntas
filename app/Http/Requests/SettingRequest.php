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
            'plama.required' => 'Password lama harus di isi.',
            'plama.current_password' => 'Password lama salah!',
            'pbaru.required' => 'Password baru harus di isi.',
            'pbaru.different' => 'Password baru harus berbeda dengan password lama.',
            'pbaru.min' => 'Password baru minimal 8 karakter.',
            'pbaru.letters' => 'Password baru harus mengandung huruf.',
            'pbaru.numbers' => 'Password baru harus mengandung angka.',
            'pbaruulangi.required' => 'Konfirmasi password baru harus di isi.',
            'pbaruulangi.same' => 'Password baru harus sama dengan konfirmasinya!',
        ];
    }
}
