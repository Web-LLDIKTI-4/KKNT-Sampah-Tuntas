<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\AjaxFormRequest;

class MahasiswaProfileRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:100'],
            'prodi' => ['required', 'string', 'max:100'],
            'tahun_masuk' => ['required', 'integer', 'between:1990,'.(date('Y') + 1)],
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
            'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'nama harus di isi.',
            'nim.required' => 'nim harus di isi.',
            'prodi.required' => 'prodi harus di isi.',
            'tahun_masuk.required' => 'tahun masuk harus di isi.',
            'tahun_masuk.integer' => 'tahun masuk harus berupa angka.',
            'phone.required' => 'phone harus di isi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka.',
            'kodept.exists' => 'Perguruan tinggi tidak valid.',
        ];
    }
}
