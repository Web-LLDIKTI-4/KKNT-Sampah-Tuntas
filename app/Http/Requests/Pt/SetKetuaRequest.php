<?php

namespace App\Http\Requests\Pt;

use App\Http\Requests\AjaxFormRequest;

class SetKetuaRequest extends AjaxFormRequest
{
    // Otorisasi per record di controller (scope visibleTo + Policy)
    public function rules(): array
    {
        return [
            'id_mahasiswa' => ['required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_mahasiswa.required' => 'Data mahasiswa tidak valid.',
            'id_mahasiswa.string' => 'Data mahasiswa tidak valid.',
            'id_mahasiswa.uuid' => 'Data mahasiswa tidak valid.',
        ];
    }
}
