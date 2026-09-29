<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            // Hanya akun mahasiswa/dpl yang boleh diubah lewat form ini
            'id' => ['required', 'uuid', Rule::exists('users', 'id')->whereIn('role', ['mahasiswa', 'dpl'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->input('id'))],
            'location_program' => ['nullable', 'uuid', 'exists:lokasi_program,id'],
            'akses' => ['nullable', Rule::in(['pjdesa', 'hapuspjdesa'])],
            'password' => ['nullable', 'string', 'max:255', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'Pengguna tidak ditemukan.',
            'name.required' => 'Nama harus di isi.',
            'email.required' => 'Surel harus diisi.',
            'email.email' => 'Surel tidak valid.',
            'email.unique' => 'Surel sudah digunakan!',
            'location_program.exists' => 'Lokasi program tidak valid.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }
}
