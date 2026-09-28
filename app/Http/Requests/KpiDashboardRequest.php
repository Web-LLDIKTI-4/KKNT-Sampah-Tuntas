<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KpiDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lokasi' => ['nullable', 'uuid', 'exists:lokasi_program,id'],
            'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            'id_target' => ['nullable', 'uuid', 'exists:kpi_target,id_target'],
        ];
    }
}
