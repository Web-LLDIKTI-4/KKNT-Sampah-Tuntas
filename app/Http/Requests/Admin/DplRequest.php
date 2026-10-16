<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Dpl;
use Illuminate\Validation\Rule;

class DplRequest extends AjaxFormRequest
{
    // Sama dengan import: spasi pada email dibuang
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($this->input('email'))) {
            $this->merge(['email' => str_replace(' ', '', trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        // Array input would make find() return a Collection
        $dpl = is_string($this->input('id_dpl')) ? Dpl::find($this->input('id_dpl')) : null;

        return [
            'id_dpl' => ['required', 'uuid', 'exists:dpl,id_dpl'],
            // Sama dengan import: NIDN unik per PT
            'nidn' => [
                'required', 'string', 'max:100',
                Rule::unique('dpl', 'nidn')->where('kodept', $this->input('kodept'))->ignore($dpl?->id_dpl, 'id_dpl'),
            ],
            // Email hanya boleh diubah selama DPL belum punya akun login
            'email' => $dpl?->hasAccount() ? ['exclude'] : [
                'required', 'email', 'max:100',
                Rule::unique('dpl', 'email')->ignore($dpl?->id_dpl, 'id_dpl'),
                Rule::unique('users', 'email'),
                // Prevent cross-role collision with mahasiswa email
                Rule::unique('mahasiswa', 'email'),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'prodi' => ['nullable', 'string', 'max:100'],
            'kodept' => ['required', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            'location_program' => ['nullable', 'uuid', 'exists:lokasi_program,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_dpl.exists' => 'DPL tidak ditemukan.',
            'nidn.required' => 'NIDN harus di isi.',
            'nidn.unique' => 'NIDN sudah terdaftar di PT tersebut!',
            'email.required' => 'Email harus di isi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 100 karakter.',
            'email.unique' => 'Email sudah terdaftar!',
            'nama.required' => 'Nama harus di isi.',
            'kodept.required' => 'Perguruan Tinggi harus di isi.',
            'kodept.exists' => 'Perguruan Tinggi tidak valid.',
            'location_program.exists' => 'Lokasi program tidak valid.',
        ];
    }
}
