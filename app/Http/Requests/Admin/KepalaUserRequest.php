<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use App\Models\User;
use App\Rules\EmailNotInPersonData;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class KepalaUserRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $update = str_ends_with($this->path(), 'updateuserkepala');
        $current = $update && is_string($this->input('id'))
            ? User::whereIn('role', ['kepala', 'pemda'])->whereKey($this->input('id'))->value('email')
            : null;

        return [
            'id' => [Rule::requiredIf($update), 'nullable', 'uuid', Rule::exists('users', 'id')->whereIn('role', ['kepala', 'pemda'])],
            'role' => ['required', 'in:kepala,pemda'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->input('id')),
                // Would otherwise occupy the account slot of a mahasiswa/DPL record; own current email is kept
                new EmailNotInPersonData($current),
            ],
            'password' => [$update ? 'nullable' : 'required', 'string', 'max:255', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role harus dipilih.',
            'role.in' => 'Role tidak valid.',
            'name.required' => 'Nama harus di isi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format Email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain!',
            'password.required' => 'Kata sandi harus diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }
}
