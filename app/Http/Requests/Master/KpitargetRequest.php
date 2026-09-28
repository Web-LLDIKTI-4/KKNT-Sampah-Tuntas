<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class KpitargetRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_target' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:kpi_target,id_target'],
            'id_kpi' => ['required', 'uuid', 'exists:kpi,id_kpi'],
            'kegiatan' => [
                'required', 'string', 'max:200',
                Rule::unique('kpi_target', 'kegiatan')
                    ->where('id_kpi', $this->input('id_kpi'))
                    ->ignore($this->input('id_target'), 'id_target'),
            ],
            'target' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'satuan' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kpi.required' => 'KPI harus dipilih.',
            'kegiatan.required' => 'Kegiatan harus diisi.',
            'kegiatan.unique' => 'Kegiatan sudah ada!',
            'target.required' => 'Target harus diisi.',
            'target.numeric' => 'Target harus angka.',
            'target.gt' => 'Target harus lebih dari 0.',
            'satuan.required' => 'Satuan harus diisi.',
        ];
    }
}
