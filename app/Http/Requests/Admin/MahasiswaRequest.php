<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Mahasiswa;
use Illuminate\Validation\Rule;

class MahasiswaRequest extends AjaxFormRequest
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
        $mahasiswa = is_string($this->input('id_mahasiswa')) ? Mahasiswa::find($this->input('id_mahasiswa')) : null;
        // Unchanged email skips unique checks so legacy duplicate rows can still be saved
        $emailChanged = ! $mahasiswa || ! is_string($this->input('email'))
            || strcasecmp($this->input('email'), str_replace(' ', '', trim((string) $mahasiswa->email))) !== 0;

        return [
            'id_mahasiswa' => ['required', 'uuid', 'exists:mahasiswa,id_mahasiswa'],
            // Sama dengan import: NIM unik per PT
            'nim' => [
                'required', 'string', 'max:100',
                Rule::unique('mahasiswa', 'nim')->where('kodept', $this->input('kodept'))->ignore($mahasiswa?->id_mahasiswa, 'id_mahasiswa'),
            ],
            'tahun_masuk' => ['nullable', 'integer', 'between:1990,'.(date('Y') + 1)],
            // Email terkunci bila sudah dipakai akun, pj_desa, atau dpl_mentoring
            'email' => $mahasiswa?->isEmailLocked() ? ['exclude'] : [
                'required', 'email', 'max:100',
                ...($emailChanged ? [
                    Rule::unique('mahasiswa', 'email')->ignore($mahasiswa?->id_mahasiswa, 'id_mahasiswa'),
                    Rule::unique('users', 'email'),
                    // Prevent cross-role collision with DPL email
                    Rule::unique('dpl', 'email'),
                ] : []),
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
            'id_mahasiswa.exists' => 'Mahasiswa tidak ditemukan.',
            'nim.required' => 'NIM harus di isi.',
            'nim.unique' => 'NIM sudah terdaftar di PT tersebut!',
            'tahun_masuk.integer' => 'Tahun masuk harus berupa angka.',
            'tahun_masuk.between' => 'Tahun masuk tidak valid.',
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
