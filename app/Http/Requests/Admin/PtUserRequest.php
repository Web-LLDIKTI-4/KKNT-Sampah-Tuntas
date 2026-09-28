<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PtUserRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        $update = $this->isUpdate() || str_ends_with($this->path(), 'updateuserpt');

        return [
            'id' => [Rule::requiredIf($update), 'nullable', 'uuid', Rule::exists('users', 'id')->where('role', 'pt')],
            'name' => ['required', 'string', 'max:255'],
            'kodept' => [
                'required', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn',
                Rule::unique('users', 'email')->ignore($this->input('id')),
            ],
            'location_program' => ['required', 'uuid', 'exists:lokasi_program,id'],
            'password' => [$update ? 'nullable' : 'required', 'string', 'max:255', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama harus di isi.',
            'kodept.required' => 'Perguruan Tinggi harus di isi.',
            'kodept.exists' => 'Perguruan Tinggi tidak valid.',
            'kodept.unique' => 'PT tersebut sudah digunakan oleh akun lain!',
            'location_program.required' => 'Lokasi program harus di isi.',
            'location_program.exists' => 'Lokasi program tidak valid.',
            'password.required' => 'Password harus di isi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
