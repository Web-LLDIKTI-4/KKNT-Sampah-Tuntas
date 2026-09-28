<?php

namespace App\Http\Requests\Dpl;

use Illuminate\Validation\Rule;

class KonversiNilaiRequest extends NilaiRequest
{
    protected function keyName(): string
    {
        return 'id_konversi';
    }

    protected function extraRules(): array
    {
        return [
            'matakuliah' => [
                'required', 'string', 'max:150',
                Rule::unique('nilai_konversi', 'matakuliah')
                    ->where('id_mahasiswa', $this->input('id_mahasiswa'))
                    ->ignore($this->input('id_konversi'), 'id_konversi'),
            ],
            'sks' => ['required', 'integer', 'between:1,24'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'matakuliah.required' => 'Matakuliah harus di isi.',
            'matakuliah.unique' => 'Matakuliah sudah terdata pada mahasiswa ini',
            'sks.required' => 'SKS harus di isi.',
            'sks.integer' => 'SKS harus angka.',
        ];
    }
}
