<?php

namespace App\Http\Requests\Pt;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Pjdesa;
use Illuminate\Validation\Rule;

class MobilisasiRequest extends AjaxFormRequest
{
    // Otorisasi per record di controller (scope visibleTo + Policy)
    public function rules(): array
    {
        return [
            'id_pjdesa' => ['required', 'string', 'uuid'],
            'status_mobilisasi' => ['required', 'string', Rule::in(Pjdesa::MOBILISASI_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'id_pjdesa.required' => 'Data ketua kelompok tidak valid.',
            'id_pjdesa.uuid' => 'Data ketua kelompok tidak valid.',
            'status_mobilisasi.required' => 'Pilih status mobilisasi.',
            'status_mobilisasi.in' => 'Status mobilisasi tidak valid.',
        ];
    }
}
