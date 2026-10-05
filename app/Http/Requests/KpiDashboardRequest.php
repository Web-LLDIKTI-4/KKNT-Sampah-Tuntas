<?php

namespace App\Http\Requests;

use App\Models\Kpisampah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KpiDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'kecamatan' => ['nullable', 'uuid', 'exists:kecamatan,id_kecamatan'],
            'desa' => ['nullable', 'uuid', 'exists:desa,id_desa'],
            'klaster' => ['nullable', 'string', Rule::in(array_keys(Kpisampah::KLASTER))],
        ];
    }
}
