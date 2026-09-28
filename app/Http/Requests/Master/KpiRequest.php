<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class KpiRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_kpi' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:kpi,id_kpi'],
            'nama_kpi' => [
                'required', 'string', 'max:255',
                Rule::unique('kpi', 'nama_kpi')->ignore($this->input('id_kpi'), 'id_kpi'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kpi.required' => 'Key performance indicator harus di isi',
            'nama_kpi.unique' => 'Key performance indicator sudah ada!',
        ];
    }
}
