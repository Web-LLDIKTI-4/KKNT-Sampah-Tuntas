<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class KepalaUserRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        $update = str_ends_with($this->path(), 'updateuserkepala');

        return [
            'id' => [Rule::requiredIf($update), 'nullable', 'uuid', Rule::exists('users', 'id')->where('role', 'kepala')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->input('id'))],
            'password' => [$update ? 'nullable' : 'required', 'string', 'max:255', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama harus di isi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format Email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain!',
            'password.required' => 'Kata sandi harus diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }
}
