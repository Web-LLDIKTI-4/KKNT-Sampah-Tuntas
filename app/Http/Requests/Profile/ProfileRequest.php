<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\AjaxFormRequest;

class ProfileRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        $rules = ['nama' => ['required', 'string', 'max:255']];

        if ($this->user()->role !== 'admin') {
            $rules += [
                'nidn' => ['required', 'string', 'max:100'],
                'prodi' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
                'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama harus di isi.',
            'nidn.required' => 'NIDN harus di isi.',
            'prodi.required' => 'Prodi harus di isi.',
            'phone.required' => 'Nomor telepon harus di isi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka.',
            'kodept.exists' => 'Perguruan tinggi tidak valid.',
        ];
    }
}
