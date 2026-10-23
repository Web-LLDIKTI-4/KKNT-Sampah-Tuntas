<?php

namespace App\Http\Requests\Pt;

use App\Http\Requests\AjaxFormRequest;

class CabutKetuaRequest extends AjaxFormRequest
{
    // Otorisasi per record di controller (scope visibleTo + Policy)
    public function rules(): array
    {
        return [
            'id_pjdesa' => ['required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_pjdesa.required' => 'Data ketua kelompok tidak valid.',
            'id_pjdesa.string' => 'Data ketua kelompok tidak valid.',
            'id_pjdesa.uuid' => 'Data ketua kelompok tidak valid.',
        ];
    }
}
