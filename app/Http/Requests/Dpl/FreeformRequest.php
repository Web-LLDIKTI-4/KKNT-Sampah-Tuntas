<?php

namespace App\Http\Requests\Dpl;

use App\Models\Freeform;
use Illuminate\Validation\Rule;

class FreeformRequest extends NilaiRequest
{
    protected function keyName(): string
    {
        return 'id_freeform';
    }

    protected function extraRules(): array
    {
        return [
            'freeform' => [
                'required', Rule::in(Freeform::KOMPONEN),
                Rule::unique('nilai_freeform', 'freeform')
                    ->where('id_mahasiswa', $this->input('id_mahasiswa'))
                    ->ignore($this->input('id_freeform'), 'id_freeform'),
            ],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'freeform.required' => 'Komponen free form harus dipilih.',
            'freeform.in' => 'Komponen free form tidak valid.',
            'freeform.unique' => 'Komponen ini sudah dinilai untuk mahasiswa tersebut.',
        ];
    }
}
