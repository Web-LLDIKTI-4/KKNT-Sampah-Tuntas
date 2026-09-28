<?php

namespace App\Http\Requests\Dpl;

use App\Http\Requests\AjaxFormRequest;

class NilaiTugasakhirRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_tugasakhir' => ['required', 'uuid'],
            'nilai_dpl' => ['required', 'integer', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nilai_dpl.required' => 'Nilai DPL harus di isi.',
            'nilai_dpl.integer' => 'Nilai DPL harus angka.',
            'nilai_dpl.between' => 'Nilai DPL harus 0 - 100.',
        ];
    }
}
