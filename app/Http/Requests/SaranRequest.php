<?php

namespace App\Http\Requests;

class SaranRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:saran,email'],
            'saran' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama harus diisi.',
            'email.required' => 'Surel harus diisi.',
            'email.email' => 'Format surel tidak valid.',
            'email.unique' => 'Pesan anda sudah ada!',
            'saran.required' => 'Saran harus isi.',
            'saran.max' => 'Saran maksimal 2000 karakter.',
        ];
    }
}
