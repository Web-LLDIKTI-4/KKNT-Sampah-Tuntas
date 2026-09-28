<?php

namespace App\Http\Requests\Dpl;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class PenilaianLogbulananRequest extends AjaxFormRequest
{
    public const PILIHAN_NILAI = ['10', '20', '30', '40', '50', '60', '70', '80', '90', '100'];

    public function rules(): array
    {
        return [
            'id_logbulanan' => ['required', 'uuid'],
            'nilai' => ['required', Rule::in(self::PILIHAN_NILAI)],
            'hasil_verifikasi' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nilai.required' => 'Nilai harus dipilih.',
            'nilai.in' => 'Nilai tidak valid.',
        ];
    }
}
